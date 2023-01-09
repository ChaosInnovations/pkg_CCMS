<?php

namespace Package\Pivel\Hydro2\Identity\Controllers;

use Package\Pivel\Hydro2\Core\Controllers\BaseController;
use Package\Pivel\Hydro2\Core\Extensions\Route;
use Package\Pivel\Hydro2\Core\Extensions\RoutePrefix;
use Package\Pivel\Hydro2\Core\Models\HTTP\Method;
use Package\Pivel\Hydro2\Core\Models\HTTP\StatusCode;
use Package\Pivel\Hydro2\Core\Models\JsonResponse;
use Package\Pivel\Hydro2\Core\Models\Response;
use Package\Pivel\Hydro2\Database\Services\DatabaseService;
use Package\Pivel\Hydro2\Email\Models\EmailMessage;
use Package\Pivel\Hydro2\Email\Services\EmailService;
use Package\Pivel\Hydro2\Identity\Extensions\Permissions;
use Package\Pivel\Hydro2\Identity\Models\User;
use Package\Pivel\Hydro2\Identity\Models\UserRole;
use Package\Pivel\Hydro2\Identity\Services\IdentityService;
use Package\Pivel\Hydro2\Identity\Views\EmailViews\NewEmailNotificationEmailView;
use Package\Pivel\Hydro2\Identity\Views\EmailViews\NewUserVerificationEmailView;

#[RoutePrefix('api/hydro2/core/identity/users')]
class IdentityController extends BaseController
{
    #[Route(Method::GET, '')]
    public function ListUsers() : Response {
        // if current user doesn't have permission pivel/hydro2/viewusers/, return 404
        if (!DatabaseService::IsPrimaryConnected()) {
            return new Response(status: StatusCode::NotFound);
        }
        if (!IdentityService::GetRequestUser($this->request)->Role->HasPermission(Permissions::ViewUsers->value)) {
            return new Response(status: StatusCode::NotFound);
        }

        /** @var User[] */
        $users = User::GetAll();

        $userResults = [];

        foreach ($users as $user) {
            $userResults[] = [
                'random_id' => $user->RandomId,
                'created' => $user->InsertedTime,
                'email' => $user->Email,
                'name' => $user->Name,
                'needs_review' => $user->NeedsReview,
                'enabled' => $user->Enabled,
                'failed_login_attempts' => $user->FailedLoginAttempts,
                'role' => [
                    'id' => $user->Role->Id,
                    'name' => $user->Role->Name,
                    'description' => $user->Role->Description,
                ],
            ];
        }

        return new JsonResponse(
            data:[
                'users' => $userResults,
            ],
        );
    }

    #[Route(Method::POST, '')]
    #[Route(Method::POST, 'create')]
    public function CreateUser() : Response {
        if (!DatabaseService::IsPrimaryConnected()) {
            return new Response(status: StatusCode::NotFound);
        }
        // if current user doesn't have permission pivel/hydro2/viewusers/, return 404,
        //  unless trying to get info about self which is always allowed
        // TODO Self-creation
        if (!IdentityService::GetRequestUser($this->request)->Role->HasPermission(Permissions::CreateUsers->value)) {
            return new Response(status: StatusCode::NotFound);
        }

        // need to provide email, name, and role (optional)
        if (!isset($this->request->Args['email'])) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'email',
                            'description' => 'New User\'s email address.',
                            'message' => 'Argument is missing.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are missing.'
            );
        }
        if (!isset($this->request->Args['name'])) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'name',
                            'description' => 'New User\'s name.',
                            'message' => 'Argument is missing.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are missing.'
            );
        }
        $roleId = $this->request->Args['role_id']??null;
        $role = null;
        if ($roleId !== null) {
            $role = UserRole::LoadFromId($roleId);
            if ($role === null) {
                return new JsonResponse(
                    data: [
                        'validation_errors' => [
                            [
                                'name' => 'role_id',
                                'description' => 'New User\'s Role.',
                                'message' => 'This user role doesn\t exist.',
                            ],
                        ],
                    ],
                    status: StatusCode::BadRequest,
                    error_message: 'One or more arguments are invalid.'
                );
            }
        } else {
            $role = IdentityService::GetDefaultUserRole();
        }

        // check that there isn't already a User with this email
        if (User::LoadFromEmail($this->request->Args['email']) !== null) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'email',
                            'description' => 'New User\'s name.',
                            'message' => 'A user already exists with this email.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are invalid.'
            );
        }

        $user = new User(
            email: $this->request->Args['email'],
            name: $this->request->Args['name'],
            enabled: true,
            role: $role,
        );

        if (!$user->Save()) {
            return new JsonResponse(
                status: StatusCode::InternalServerError,
                error_message: "There was a problem with the database."
            );
        }

        $emailProfileProvider = EmailService::GetOutboundEmailProviderInstance('noreply');
        if ($emailProfileProvider === null) {
            return new JsonResponse(
                status: StatusCode::InternalServerError,
                error_message: "Unable to send validation email."
            );
        }
        $view = new NewUserVerificationEmailView(IdentityService::GetEmailVerificationUrl($this->request, $user, true), $user->Name);
        $message = new EmailMessage($view, [$user->Email]);
        if (!$emailProfileProvider->SendEmail($message)) {
            return new JsonResponse(
                status: StatusCode::InternalServerError,
                error_message: "Unable to send validation email."
            );
        }

        return new JsonResponse(
            data: [
                'new_user' => [
                    'id' => $user->RandomId,
                ],
            ],
            status:StatusCode::OK
        );
    }

    #[Route(Method::GET, '{id}')]
    public function ListUser() : Response {
        if (!DatabaseService::IsPrimaryConnected()) {
            return new Response(status: StatusCode::NotFound);
        }
        // if current user doesn't have permission pivel/hydro2/viewusers/, return 404,
        //  unless trying to get info about self which is always allowed
        if (!(
            IdentityService::GetRequestUser($this->request)->Role->HasPermission(Permissions::ViewUsers->value) ||
            IdentityService::GetRequestUser($this->request)->RandomId === ($this->request->Args['id']??null)
            )) {
            return new Response(status: StatusCode::NotFound);
        }

        $user = User::LoadFromRandomId($this->request->Args['id']);

        if ($user === null) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'id',
                            'description' => 'User ID.',
                            'message' => 'This user doesn\'t exist.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are invalid.'
            );
        }

        $userResults = [[
            'random_id' => $user->RandomId,
            'created' => $user->InsertedTime,
            'email' => $user->Email,
            'name' => $user->Name,
            'needs_review' => $user->NeedsReview,
            'enabled' => $user->Enabled,
            'failed_login_attempts' => $user->FailedLoginAttempts,
            'role' => [
                'id' => $user->Role->Id,
                'name' => $user->Role->Name,
                'description' => $user->Role->Description,
            ],
        ]];

        return new JsonResponse(
            data:[
                'users' => $userResults,
            ],
        );
    }

    #[Route(Method::POST, '{id}')]
    #[Route(Method::POST, '{id}/update')]
    public function UpdateUser() : Response {
        if (!DatabaseService::IsPrimaryConnected()) {
            return new Response(status: StatusCode::NotFound);
        }
        // if manageuser or if self.
        if (!(
            IdentityService::GetRequestUser($this->request)->Role->HasPermission(Permissions::ManageUsers->value) ||
            IdentityService::GetRequestUser($this->request)->RandomId === ($this->request->Args['id']??null)
            )) {
            return new Response(status: StatusCode::NotFound);
        }

        $user = User::LoadFromRandomId($this->request->Args['id']);

        if ($user === null) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'id',
                            'description' => 'User ID.',
                            'message' => 'This user doesn\'t exist.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are invalid.'
            );
        }

        $email = $this->request->Args['email']??$user->Email;
        $emailChanged = $email !== $user->Email;
        $oldEmail = $user->Email;
        $user->Email = $email;
        $user->EmailVerified = $user->EmailVerified && (!$emailChanged);
        $user->Name = $this->request->Args['name']??$user->Name;

        // the following fields cannot be edited by self/without manageuser permissions:
        //  Role
        //  NeedsReview
        //  Enabled
        //  FailedLogin/2FAAttempts (to unlock a user's login attempts)
        $roleId = $this->request->Args['role_id']??null;
        $role = null;
        if ($roleId !== null) {
            $role = UserRole::LoadFromId($roleId);
            if ($role === null) {
                return new JsonResponse(
                    data: [
                        'validation_errors' => [
                            [
                                'name' => 'role_id',
                                'description' => 'New User\'s Role.',
                                'message' => 'This user role doesn\t exist.',
                            ],
                        ],
                    ],
                    status: StatusCode::BadRequest,
                    error_message: 'One or more arguments are invalid.'
                );
            }
        } else {
            $role = $user->Role;
        }
        
        if (IdentityService::GetRequestUser($this->request)->Role->HasPermission(Permissions::ManageUsers->value)) {
            $user->Role = $role;
            $user->NeedsReview = $this->request->Args['needs_review']??$user->NeedsReview;
            $user->Enabled = $this->request->Args['enabled']??$user->Enabled;
            if ($this->request->Args['reset_failed_login_attempts']??false) {
                $user->FailedLoginAttempts = 0;
            }
            if ($this->request->Args['reset_failed_2fa_attempts']??false) {
                $user->Failed2FAAttempts = 0;
            }
        }

        if (!$user->Save()) {
            return new JsonResponse(
                status: StatusCode::InternalServerError,
                error_message: "There was a problem with the database."
            );
        }

        if ($emailChanged) {
            $emailProfileProvider = EmailService::GetOutboundEmailProviderInstance('noreply');
            if ($emailProfileProvider === null) {
                return new JsonResponse(
                    status: StatusCode::InternalServerError,
                    error_message: "Unable to send validation email."
                );
            }
            $newEmailView = new NewUserVerificationEmailView(IdentityService::GetEmailVerificationUrl($this->request, $user, true), $user->Name);
            $newEmailMessage = new EmailMessage($newEmailView, [$user->Email]);
            if (!$emailProfileProvider->SendEmail($newEmailMessage)) {
                return new JsonResponse(
                    status: StatusCode::InternalServerError,
                    error_message: "Unable to send validation email."
                );
            }
            $oldEmailView = new NewEmailNotificationEmailView($user->Name, $oldEmail, $user->Email);
            $oldEmailMessage = new EmailMessage($oldEmailView, [$oldEmail]);
            if (!$emailProfileProvider->SendEmail($oldEmailMessage)) {
                return new JsonResponse(
                    status: StatusCode::InternalServerError,
                    error_message: "Unable to send validation email."
                );
            }
        }

        return new JsonResponse(
            data: [
                'new_user' => [
                    'id' => $user->RandomId,
                ],
            ],
            status:StatusCode::OK
        );
    }

    #[Route(Method::POST, '{id}/remove')]
    #[Route(Method::DELETE, '{id}')]
    public function DeleteUser() : Response {
        if (!DatabaseService::IsPrimaryConnected()) {
            return new Response(status: StatusCode::NotFound);
        }
        // if current user doesn't have permission pivel/hydro2/manageusers/, return 404
        if (!IdentityService::GetRequestUser($this->request)->Role->HasPermission(Permissions::ManageUsers->value)) {
            return new Response(status: StatusCode::NotFound);
        }

        $user = User::LoadFromRandomId($this->request->Args['id']);

        if ($user != null && !$user->Delete()) {
            return new JsonResponse(
                status: StatusCode::InternalServerError,
                error_message: "There was a problem with the database."
            );
        }

        return new JsonResponse(status:StatusCode::OK);
    }

    // TODO Implement UserChangePassword
    #[Route(Method::POST, '{id}/changepassword')]
    public function UserChangePassword() : Response {
        // check for valid id
        // check that either the existing password or a valid passwordreset token was provided
        // insert new password record
        // send notification email
        return new JsonResponse(
            status:StatusCode::InternalServerError,
            error_message:'Route exists but not implemented.',
        );
    }

    // TODO Implement UserSendResetPassword
    #[Route(Method::POST, '{id}/sendpasswordreset')]
    public function UserSendResetPassword() : Response {
        return new JsonResponse(
            status:StatusCode::InternalServerError,
            error_message:'Route exists but not implemented.',
        );
    }

    // TODO Implement VerifyUser (someone would go to this URI from a link inside the email.
    //  need to include the view they would see in the response, or redirect to a 'verified'
    //  page instead)
    #[Route(Method::GET, '~verifyuseremail/{id}')]
    public function UserVerify() : Response {
        if (!isset($this->request->Args['token'])) {
            // missing argument
            return new Response(
                content:"This verification link is not valid.",
            );
        }

        $user = User::LoadFromRandomId($this->request->Args['id']);

        if ($user === null) {
            return new Response(
                content:"This verification link is not valid.",
            );
        }

        if (!$user->ValidateEmailVerificationToken($this->request->Args['token'])) {
            return new Response(
                content:"This verification link is not valid.",
            );
        }

        return new Response(
            content:"Thanks, your email is verified.",
        );
    }

    #[Route(Method::GET, '~resetpassword/{id}')]
    public function UserResetPasswordView() : Response {
        return new JsonResponse(
            status:StatusCode::InternalServerError,
            error_message:'Route exists but not implemented.',
        );
    }
}