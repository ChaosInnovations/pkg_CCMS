<?php

namespace Package\Pivel\Hydro2\Controllers;

use DateTime;
use DateTimeZone;
use Package\Pivel\Hydro2\Core\Controllers\BaseController;
use Package\Pivel\Hydro2\Core\Extensions\Route;
use Package\Pivel\Hydro2\Core\Extensions\RoutePrefix;
use Package\Pivel\Hydro2\Core\Models\HTTP\Method;
use Package\Pivel\Hydro2\Core\Models\HTTP\StatusCode;
use Package\Pivel\Hydro2\Core\Models\JsonResponse;
use Package\Pivel\Hydro2\Core\Models\Response;
use Package\Pivel\Hydro2\Database\Extensions\OrderBy;
use Package\Pivel\Hydro2\Database\Models\Order;
use Package\Pivel\Hydro2\Database\Services\DatabaseService;
use Package\Pivel\Hydro2\Identity\Models\PasswordResetToken;
use Package\Pivel\Hydro2\Identity\Models\Permissions;
use Package\Pivel\Hydro2\Identity\Models\User;
use Package\Pivel\Hydro2\Identity\Models\UserPassword;
use Package\Pivel\Hydro2\Identity\Models\UserRole;
use Package\Pivel\Hydro2\Identity\Services\IdentityService;
use Package\Pivel\Hydro2\Identity\Views\EmailViews\NewEmailNotificationEmailView;
use Package\Pivel\Hydro2\Identity\Views\EmailViews\NewUserVerificationEmailView;
use Package\Pivel\Hydro2\Identity\Views\EmailViews\PasswordChangedNotificationEmailView;
use Package\Pivel\Hydro2\Identity\Views\EmailViews\PasswordResetEmailView;
use Package\Pivel\Hydro2\Identity\Views\ResetView;
use Package\Pivel\Hydro2\Identity\Views\VerifyView;

#[RoutePrefix('api/hydro2/identity/users')]
class UserController extends BaseController
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

        $order = null;
        if (isset($this->request->Args['sort_by'])) {
            if ($this->request->Args['sort_by'] == 'role') {
                $this->request->Args['sort_by'] = 'user_role_id';
            }
            if ($this->request->Args['sort_by'] == 'created') {
                $this->request->Args['sort_by'] = 'inserted';
            }
            $dir = Order::tryFrom(strtoupper($this->request->Args['sort_dir']??'asc'))??Order::Ascending;
            $order = (new OrderBy)->Column($this->request->Args['sort_by']??'email', $dir);
        }
        $limit = $this->request->Args['limit']??null;
        $offset = $this->request->Args['offset']??null;

        // TODO implement filtering better
        /** @var User[] */
        $users = [];
        if (isset($this->request->Args['role_id']) && UserRole::LoadFromId($this->request->Args['role_id']) !== null) {
            /** @var User[] */
            $users = User::GetAllWithRole(UserRole::LoadFromId($this->request->Args['role_id']));
        } else {
            /** @var User[] */
            $users = User::GetAll($order, $limit, $offset);
        }
        
        $userResults = [];

        foreach ($users as $user) {
            $userResults[] = [
                'random_id' => $user->RandomId,
                'created' => $user->InsertedTime,
                'email' => $user->Email,
                'email_verified' => $user->EmailVerified,
                'name' => $user->Name,
                'needs_review' => $user->NeedsReview,
                'enabled' => $user->Enabled,
                'failed_login_attempts' => $user->FailedLoginAttempts,
                'failed_2fa_attempts' => $user->Failed2FAAttempts,
                'role' => ($user->Role == null ? null : [
                    'id' => $user->Role->Id,
                    'name' => $user->Role->Name,
                    'description' => $user->Role->Description,
                ]),
            ];
        }

        return new JsonResponse(
            data:[
                'users' => $userResults,
            ],
        );
    }

    #[Route(Method::POST, '')]
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
                            'description' => "New User's email address.",
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
                            'description' => "New User's name.",
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
                                'description' => "New User's Role.",
                                'message' => "This user role doesn't exist.",
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
        echo $role->Id;

        // check that there isn't already a User with this email
        if (User::LoadFromEmail($this->request->Args['email']) !== null) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'email',
                            'description' => "New User's name.",
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

        $view = new NewUserVerificationEmailView(IdentityService::GetEmailVerificationUrl($this->request, $user, true), $user->Name);
        if (!IdentityService::SendEmailToUser($user, $view)) {
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
                            'message' => "This user doesn't exist.",
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
            'email_verified' => $user->EmailVerified,
            'name' => $user->Name,
            'needs_review' => $user->NeedsReview,
            'enabled' => $user->Enabled,
            'failed_login_attempts' => $user->FailedLoginAttempts,
            'failed_2fa_attempts' => $user->Failed2FAAttempts,
            'role' => ($user->Role == null ? null : [
                'id' => $user->Role->Id,
                'name' => $user->Role->Name,
                'description' => $user->Role->Description,
            ]),
        ]];

        return new JsonResponse(
            data:[
                'users' => $userResults,
            ],
        );
    }

    #[Route(Method::POST, '{id}')]
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
                            'message' => "This user doesn't exist.",
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
                                'description' => "New User's Role.",
                                'message' => "This user role doesn't exist.",
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
            $newEmailView = new NewUserVerificationEmailView(IdentityService::GetEmailVerificationUrl($this->request, $user, true), $user->Name);
            $oldEmailView = new NewEmailNotificationEmailView($user->Name, $oldEmail, $user->Email);
            if (!(IdentityService::SendEmailToUser($user, $newEmailView) && IdentityService::SendEmailToUser(new User($oldEmail, $user->Name), $oldEmailView))) {
                return new JsonResponse(
                    status: StatusCode::InternalServerError,
                    error_message: "Unable to send validation email."
                );
            }
        }

        return new JsonResponse(
            status:StatusCode::OK
        );
    }

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

    // TODO add 2FA for changing passwords if set up
    #[Route(Method::POST, '{id}/changepassword')]
    #[Route(Method::POST, '~api/hydro2/identity/changeuserpassword')]
    public function UserChangePassword() : Response {
        if (!DatabaseService::IsPrimaryConnected()) {
            return new JsonResponse(
                status: StatusCode::InternalServerError,
                error_message: "There was a problem with the database."
            );
        }

        $user = User::LoadFromRandomId($this->request->Args['id']??'');
        if ($user === null) {
            $user = User::LoadFromEmail($this->request->Args['email']??'');
        }
        if ($user === null) {
            $user = IdentityService::GetRequestUser($this->request);
        }

        if ($user === null) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'id',
                            'description' => 'User ID.',
                            'message' => "This user doesn't exist.",
                        ],
                        
                        [
                            'name' => 'email',
                            'description' => "User's email address.",
                            'message' => "This user doesn't exist.",
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are invalid.'
            );
        }

        // check that either the existing password or a valid passwordreset token was provided
        $password = $this->request->Args['password']??null;
        $reset_token = $this->request->Args['reset_token']??null;
        if (!($password === null || $reset_token === null)) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'password',
                            'description' => "User's current password",
                            'message' => "Either the user's current password or a valid reset token are required.",
                        ],
                        [
                            'name' => 'reset_token',
                            'description' => 'Password reset token.',
                            'message' => "Either the user's current password or a valid reset token are required.",
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are missing.'
            );
        }
        $currentPassword = $user->GetCurrentPassword();
        if ($password !== null && ($currentPassword == null || !($currentPassword->ComparePassword($password)))) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'password',
                            'description' => "User's current password",
                            'message' => 'The provided password is incorrect.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are invalid.'
            );
        }
        if ($reset_token !== null && !IdentityService::IsPasswordResetTokenValid($reset_token, $user)) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'reset_token',
                            'description' => 'Password reset token.',
                            'message' => 'The provided password reset token is incorrect, expired, or already used.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are invalid.'
            );
        }

        // check that the new password was provided.
        if (!isset($this->request->Args['new_password'])) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'new_password',
                            'description' => "User's new password",
                            'message' => "The user's new password.",
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are missing.'
            );
        }

        // send notification email
        $emailView = new PasswordChangedNotificationEmailView($user->Name);
        if (!IdentityService::SendEmailToUser($user, $emailView)) {
            return new JsonResponse(
                status: StatusCode::InternalServerError,
                error_message: "Unable to send notification email."
            );
        }

        // insert new password record
        $expiry = null;
        if ($user->Role->MaxPasswordAgeDays !== null) {
            $expiry = new DateTime(timezone:new DateTimeZone('UTC'));
            $expiry->modify("+{$user->Role->MaxPasswordAgeDays} days");
        }
        $newPassword = new UserPassword($user->Id,$this->request->Args['new_password'],new DateTime(timezone:new DateTimeZone('UTC')),$expiry);
        if (!$newPassword->Save()) {
            return new JsonResponse(
                status: StatusCode::InternalServerError,
                error_message: "There was a problem with the database."
            );
        }

        return new JsonResponse(status: StatusCode::OK);
    }

    #[Route(Method::POST, '{id}/sendpasswordreset')]
    #[Route(Method::POST, '~api/hydro2/identity/sendpasswordreset')]
    public function UserSendResetPassword() : Response {
        if (!DatabaseService::IsPrimaryConnected()) {
            return new JsonResponse(
                status: StatusCode::InternalServerError,
                error_message: "There was a problem with the database."
            );
        }

        $user = User::LoadFromRandomId($this->request->Args['id']??'');
        if ($user === null) {
            $user = User::LoadFromEmail($this->request->Args['email']??'');
        }

        if ($user === null) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'id',
                            'description' => 'User ID.',
                            'message' => "This user doesn't exist.",
                        ],
                        
                        [
                            'name' => 'email',
                            'description' => "User's email address.",
                            'message' => "This user doesn't exist.",
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are invalid.'
            );
        }

        $token = new PasswordResetToken($user->Id);
        $token->Save();

        // send reset email
        $emailView = new PasswordResetEmailView(IdentityService::GetPasswordResetUrl($this->request, $user, $token), $user->Name, 10);
        if (!IdentityService::SendEmailToUser($user, $emailView)) {
            return new JsonResponse(
                status: StatusCode::InternalServerError,
                error_message: "Unable to send password reset email."
            );
        }

        if (!$token->Save()) {
            return new JsonResponse(
                status: StatusCode::InternalServerError,
                error_message: "There was a problem with the database."
            );
        }

        return new JsonResponse(status:StatusCode::OK);
    }

    #[Route(Method::GET, '~verifyuseremail/{id}')]
    #[Route(Method::GET, '~verifyuseremail')]
    public function UserVerify() : Response {
        $view = new VerifyView(false);
        if (!isset($this->request->Args['token'])) {
            // missing argument
            return new Response(
                content:$view->Render(),
            );
        }

        $user = User::LoadFromRandomId($this->request->Args['id']??'');

        if ($user === null) {
            return new Response(
                content:$view->Render(),
            );
        }
        
        $view->SetUserId($this->request->Args['id']);

        if (!$user->ValidateEmailVerificationToken($this->request->Args['token'])) {
            return new Response(
                content:$view->Render(),
            );
        }

        $user->EmailVerified = true;

        if (!$user->Save()) {
            return new Response(
                status:StatusCode::InternalServerError,
                content:"This verification link is valid, but there was a problem with the database.",
            );
        }

        // If the user has not yet set a password, generate password reset token.
        $userNeedsToCreatePassword = (UserPassword::LoadCurrentFromUser($user) === null);
        if ($userNeedsToCreatePassword) {
            $PasswordResetToken = new PasswordResetToken($user->Id, expireAfterMinutes: 60);
            $PasswordResetToken->Save();
            $view->SetIsPasswordChangeRequired(true);
            $view->SetPasswordResetToken($PasswordResetToken);
        }

        $view->SetIsValid(true);

        return new Response(
            content:$view->Render(),
        );
    }

    #[Route(Method::GET, '~resetpassword/{id}')]
    #[Route(Method::GET, '~resetpassword')]
    public function UserResetPasswordView() : Response {
        $view = new ResetView(false);
        if (!isset($this->request->Args['token'])) {
            // missing argument
            return new Response(
                content:$view->Render(),
            );
        }

        $user = User::LoadFromRandomId($this->request->Args['id']??'');
        if ($user === null) {
            return new Response(
                content:$view->Render(),
            );
        }
        
        $view->SetUserId($this->request->Args['id']);

        $resetToken = PasswordResetToken::LoadFromToken($this->request->Args['token']??'');
        if ($resetToken === null) {
            return new Response(
                content:$view->Render(),
            );
        }

        if (!$resetToken->CompareToken($this->request->Args['token']??'') || $resetToken->UserId !== $user->Id) {
            return new Response(
                content:$view->Render(),
            );
        }

        $view->SetPasswordResetToken($resetToken);
        $view->SetIsValid(true);

        return new Response(
            content:$view->Render(),
        );
    }
}