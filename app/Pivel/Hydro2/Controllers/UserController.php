<?php

namespace Pivel\Hydro2\Controllers;

use Pivel\Hydro2\Extensions\Query;
use Pivel\Hydro2\Extensions\Route;
use Pivel\Hydro2\Extensions\RoutePrefix;
use Pivel\Hydro2\Models\Database\Order;
use Pivel\Hydro2\Models\HTTP\JsonResponse;
use Pivel\Hydro2\Models\HTTP\Method;
use Pivel\Hydro2\Models\HTTP\Request;
use Pivel\Hydro2\Models\HTTP\Response;
use Pivel\Hydro2\Models\HTTP\StatusCode;
use Pivel\Hydro2\Models\Identity\User;
use Pivel\Hydro2\Models\Permissions;
use Pivel\Hydro2\Services\Identity\IIdentityService;
use Pivel\Hydro2\Services\IdentityService;
use Pivel\Hydro2\Services\ILoggerService;
use Pivel\Hydro2\Services\UserNotificationService;
use Pivel\Hydro2\Views\EmailViews\Identity\NewEmailNotificationEmailView;
use Pivel\Hydro2\Views\EmailViews\Identity\NewUserVerificationEmailView;
use Pivel\Hydro2\Views\EmailViews\Identity\PasswordChangedNotificationEmailView;
use Pivel\Hydro2\Views\EmailViews\Identity\PasswordResetEmailView;
use Pivel\Hydro2\Views\Identity\ResetView;
use Pivel\Hydro2\Views\Identity\VerifyView;

#[RoutePrefix('api/hydro2/identity/users')]
class UserController extends BaseController
{
    private ILoggerService $_logger;
    private IIdentityService $_identityService;
    private UserNotificationService $_userNotificationService;

    public function __construct(
        ILoggerService $logger,
        IIdentityService $identityService,
        UserNotificationService $userNotificationService,
        Request $request,
    ) {
        $this->_logger = $logger;
        $this->_identityService = $identityService;
        $this->_userNotificationService = $userNotificationService;
        parent::__construct($request);
    }
    
    #[Route(Method::GET, '')]
    public function ListUsers(): Response
    {
        // if current user doesn't have permission pivel/hydro2/viewusers/, return 404
        $requestUser = $this->_identityService->GetUserFromRequestOrVisitor($this->request);
        if (!$requestUser->GetUserRole()->HasPermission(Permissions::ViewUsers->value)) {
            return new Response(status: StatusCode::NotFound);
        }

        $query = new Query();
        $query->Limit($this->request->Args['limit'] ?? -1);
        $query->Offset($this->request->Args['offset'] ?? 0);
        
        if (isset($this->request->Args['sort_by'])) {
            if ($this->request->Args['sort_by'] == 'role') {
                $this->request->Args['sort_by'] = 'user_role_id';
            }
            if ($this->request->Args['sort_by'] == 'created') {
                $this->request->Args['sort_by'] = 'inserted';
            }
            $dir = Order::tryFrom(strtoupper($this->request->Args['sort_dir']??'asc'))??Order::Ascending;
            $query->OrderBy($this->request->Args['sort_by']??'email', $dir);
        }

        if (isset($this->request->Args['q']) && !empty($this->request->Args['q'])) {
            $query->Like('email', '%' . str_replace('%', '\\%', str_replace('_', '\\_', $this->request->Args['q'])) . '%');
            $query = (new Query())
                ->Like('name', '%' . str_replace('%', '\\%', str_replace('_', '\\_', $this->request->Args['q'])) . '%')
                ->Or($query);
        }

        // TODO implement filtering for more fields?
        if (isset($this->request->Args['role_id'])) {
            $query->Equal('user_role_id', $this->request->Args['role_id']);
        }

        $users = $this->_identityService->GetUsersMatchingQuery($query);
        
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
                'role' => ($user->GetUserRole() == null ? null : [
                    'id' => $user->GetUserRole()->Id,
                    'name' => $user->GetUserRole()->Name,
                    'description' => $user->GetUserRole()->Description,
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
    public function CreateUser(): Response
    {
        // if current user doesn't have permission pivel/hydro2/viewusers/, return 404
        $requestUser = $this->_identityService->GetUserFromRequestOrVisitor($this->request);
        if (!$requestUser->GetUserRole()->HasPermission(Permissions::CreateUsers->value)) {
            return new Response(status: StatusCode::NotFound);
        }

        // need to provide email, name, and role (optional)
        if (empty($this->request->Args['email'])) {
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

        // check that provided role (optional) exists. If not, return error. If so, proceed using the default new user role.
        $roleId = $this->request->Args['role_id']??null;
        $role = null;
        if ($roleId !== null) {
            $role = $this->_identityService->GetUserRoleFromId($roleId);
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
            $role = $this->_identityService->GetDefaultNewUserRole();
        }

        // check that there isn't already a User with this email
        if ($this->_identityService->IsEmailInUseByUser($this->request->Args['email'])) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'email',
                            'description' => "New User's email.",
                            'message' => 'A user already exists with this email.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are invalid.'
            );
        }

        $newUser = $this->_identityService->CreateNewUser(
            email: $this->request->Args['email'],
            name: $this->request->Args['name'],
            isEnabled: $this->request->Args['enabled']??true,
            role: $role,
        );

        $newUser->NeedsReview = $this->request->Args['needs_review']??false;

        if ($newUser === null) {
            return new JsonResponse(
                status: StatusCode::InternalServerError,
                error_message: "There was a problem with the database."
            );
        }

        $view = new NewUserVerificationEmailView($this->_identityService->GetEmailVerificationUrl($this->request, $newUser, true), $newUser->Name);
        if (!$this->_userNotificationService->SendEmailToUser($newUser, $view)) {
            return new JsonResponse(
                status: StatusCode::InternalServerError,
                error_message: "Unable to send validation email."
            );
        }

        return new JsonResponse(
            data: [
                'new_user' => [
                    'id' => $newUser->RandomId,
                ],
            ],
            status:StatusCode::OK
        );
    }

    #[Route(Method::GET, '{id}')]
    public function ListUser(): Response
    {
        // if current user doesn't have permission pivel/hydro2/viewusers/, return 404,
        //  unless trying to get info about self which is always allowed
        $requestUser = $this->_identityService->GetUserFromRequestOrVisitor($this->request);
        if (!(
            $requestUser->GetUserRole()->HasPermission(Permissions::ViewUsers->value) ||
            $requestUser->RandomId === ($this->request->Args['id'])
        )) {
            return new Response(status: StatusCode::NotFound);
        }

        $user = $this->_identityService->GetUserFromRandomId($this->request->Args['id']);

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
            'role' => ($user->GetUserRole() == null ? null : [
                'id' => $user->GetUserRole()->Id,
                'name' => $user->GetUserRole()->Name,
                'description' => $user->GetUserRole()->Description,
            ]),
        ]];

        return new JsonResponse(
            data:[
                'users' => $userResults,
            ],
        );
    }

    #[Route(Method::POST, '{id}')]
    public function UpdateUser(): Response
    {
        // if current user doesn't have permission pivel/hydro2/manageuser, return 404,
        //  unless trying to modify self (if without manageuser permission, can only edit a restricted set of properties)
        $requestUser = $this->_identityService->GetUserFromRequestOrVisitor($this->request);
        if (!(
            $requestUser->GetUserRole()->HasPermission(Permissions::ManageUsers->value) ||
            $requestUser->RandomId === ($this->request->Args['id'])
        )) {
            return new Response(status: StatusCode::NotFound);
        }

        $user = $this->_identityService->GetUserFromRandomId($this->request->Args['id']);

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

        $email = empty($this->request->Args['email'])?$user->Email:$this->request->Args['email'];
        $emailChanged = $email !== $user->Email;
        // check that there isn't already a User with this email
        if ($emailChanged && $this->_identityService->IsEmailInUseByUser($email)) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'email',
                            'description' => "User's email address.",
                            'message' => 'A user already exists with this email.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are invalid.'
            );
        }
        $oldEmail = $user->Email;
        $user->Email = $email;
        $user->EmailVerified = $user->EmailVerified && (!$emailChanged);
        $user->Name = $this->request->Args['name']??$user->Name;

        // the following fields cannot be edited by self/without manageuser permissions:
        //  Role
        //  NeedsReview
        //  Enabled
        //  FailedLogin/2FAAttempts (to unlock a user's login attempts)        
        if ($requestUser->GetUserRole()->HasPermission(Permissions::ManageUsers->value)) {
            $roleId = $this->request->Args['role_id']??null;
            if ($roleId !== null) {
                $user->SetUserRole($this->_identityService->GetUserRoleFromId($roleId));
                if ($user->GetUserRole() === null) {
                    return new JsonResponse(
                        data: [
                            'validation_errors' => [
                                [
                                    'name' => 'role_id',
                                    'description' => "User's Role.",
                                    'message' => "This user role doesn't exist.",
                                ],
                            ],
                        ],
                        status: StatusCode::BadRequest,
                        error_message: 'One or more arguments are invalid.'
                    );
                }
            }

            $user->NeedsReview = $this->request->Args['needs_review']??$user->NeedsReview;
            $user->Enabled = $this->request->Args['enabled']??$user->Enabled;

            if ($this->request->Args['reset_failed_login_attempts']??false) {
                $user->FailedLoginAttempts = 0;
            }

            if ($this->request->Args['reset_failed_2fa_attempts']??false) {
                $user->Failed2FAAttempts = 0;
            }
        }

        if (!$this->_identityService->UpdateUser($user)) {
            return new JsonResponse(
                status: StatusCode::InternalServerError,
                error_message: "There was a problem with the database."
            );
        }

        if ($emailChanged) {
            $newEmailView = new NewUserVerificationEmailView($this->_identityService->GetEmailVerificationUrl($this->request, $user, true), $user->Name);
            $oldEmailView = new NewEmailNotificationEmailView($user->Name, $oldEmail, $user->Email);
            if (!(
                $this->_userNotificationService->SendEmailToUser($user, $newEmailView) &&
                $this->_userNotificationService->SendEmailToUser(new User($oldEmail, $user->Name), $oldEmailView)
            )) {
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
    public function DeleteUser(): Response
    {
        // if current user doesn't have permission pivel/hydro2/viewusers/, return 404
        $requestUser = $this->_identityService->GetUserFromRequestOrVisitor($this->request);
        if (!$requestUser->GetUserRole()->HasPermission(Permissions::ManageUsers->value)) {
            return new Response(status: StatusCode::NotFound);
        }

        $user = $this->_identityService->GetUserFromRandomId($this->request->Args['id']);

        if ($user != null && !$this->_identityService->DeleteUser($user)) {
            return new JsonResponse(
                status: StatusCode::InternalServerError,
                error_message: "There was a problem with the database."
            );
        }

        // TODO send notification email to user when account is deleted.

        return new JsonResponse(status:StatusCode::OK);
    }

    // TODO add 2FA for changing passwords if set up
    #[Route(Method::POST, '{id}/changepassword')]
    #[Route(Method::POST, '~api/hydro2/identity/changeuserpassword')]
    public function UserChangePassword(): Response
    {
        $user = $this->_identityService->GetUserFromRandomId($this->request->Args['id']??'');
        if ($user === null) {
            $user = $this->_identityService->GetUserFromEmail($this->request->Args['email']??'');
        }
        if ($user === null) {
            $user = $this->_identityService->GetUserFromRequestOrVisitor($this->request);
        }

        if ($user === null || $user->Id == $this->_identityService->GetVisitorUser()->Id) {
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
        if ($password !== null && !$user->CheckPassword($password)) {
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
        if ($reset_token !== null && !$user->CheckPasswordResetToken($reset_token)) {
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

        // send notification email. If unable to send, fail to change password.
        $emailView = new PasswordChangedNotificationEmailView($user->Name);
        if (!$this->_userNotificationService->SendEmailToUser($user, $emailView)) {
            return new JsonResponse(
                status: StatusCode::InternalServerError,
                error_message: "Unable to send notification email."
            );
        }

        if (!$user->SetNewPassword($this->request->Args['new_password'])) {
            return new JsonResponse(
                status: StatusCode::InternalServerError,
                error_message: "There was a problem with the database."
            );
        }

        return new JsonResponse(status: StatusCode::OK);
    }

    #[Route(Method::POST, '{id}/sendpasswordreset')]
    #[Route(Method::POST, '~api/hydro2/identity/sendpasswordreset')]
    public function UserSendResetPassword(): Response
    {
        $user = $this->_identityService->GetUserFromRandomId($this->request->Args['id']??'');
        if ($user === null) {
            $user = $this->_identityService->GetUserFromEmail($this->request->Args['email']??'');
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

        $token = $user->CreateNewPasswordResetToken();
        if ($token === null) {
            return new JsonResponse(
                status: StatusCode::InternalServerError,
                error_message: "There was a problem with the database."
            );
        }

        // send reset email
        $emailView = new PasswordResetEmailView($this->_identityService->GetPasswordResetUrl($this->request, $user, $token), $user->Name, 10);
        if (!$this->_userNotificationService->SendEmailToUser($user, $emailView)) {
            return new JsonResponse(
                status: StatusCode::InternalServerError,
                error_message: "Unable to send password reset email."
            );
        }

        return new JsonResponse(status:StatusCode::OK);
    }

    #[Route(Method::GET, '~verifyuseremail/{id}')]
    #[Route(Method::GET, '~verifyuseremail')]
    public function UserVerify(): Response {
        $view = new VerifyView(false);
        if (!isset($this->request->Args['token'])) {
            // missing argument
            return new Response(
                content:$view->Render(),
            );
        }

        $user = $this->_identityService->GetUserFromRandomId($this->request->Args['id']??'');

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

        if (!$this->_identityService->UpdateUser($user)) {
            return new Response(
                status:StatusCode::InternalServerError,
                content:"This verification link is valid, but there was a problem with the database.",
            );
        }

        // If the user has not yet set a password, generate password reset token.
        if ($user->IsPasswordChangeRequired()) {
            $token = $user->CreateNewPasswordResetToken();
            if ($token == null) {
                return new Response(
                    status:StatusCode::InternalServerError,
                    content:"This verification link is valid, but there was a problem with the database.",
                );
            }

            $view->SetIsPasswordChangeRequired(true);
            $view->SetPasswordResetToken($token);
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

        $user = $this->_identityService->GetUserFromRandomId($this->request->Args['id']??'');
        if ($user === null) {
            return new Response(
                content:$view->Render(),
            );
        }
        
        $view->SetUserId($user->Id);

        if (!$user->CheckPasswordResetToken($this->request->Args['token']??'')) {
            return new Response(
                content:$view->Render(),
            );
        }

        $view->SetPasswordResetToken($this->_identityService->GetPasswordResetTokenFromString($this->request->Args['token']??''));
        $view->SetIsValid(true);

        return new Response(
            content:$view->Render(),
        );
    }
}