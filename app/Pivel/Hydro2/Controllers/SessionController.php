<?php

namespace Pivel\Hydro2\Controllers;

use DateTime;
use DateTimeZone;
use Pivel\Hydro2\Extensions\Route;
use Pivel\Hydro2\Extensions\RoutePrefix;
use Pivel\Hydro2\Models\HTTP\JsonResponse;
use Pivel\Hydro2\Models\HTTP\Method;
use Pivel\Hydro2\Models\HTTP\Request;
use Pivel\Hydro2\Models\HTTP\Response;
use Pivel\Hydro2\Models\HTTP\StatusCode;
use Pivel\Hydro2\Models\Identity\Session;
use Pivel\Hydro2\Models\Identity\User;
use Pivel\Hydro2\Models\Identity\UserPassword;
use Pivel\Hydro2\Models\Permissions;
use Pivel\Hydro2\Services\Database\DatabaseService;
use Pivel\Hydro2\Services\IdentityService;
use Pivel\Hydro2\Services\ILoggerService;
use Pivel\Hydro2\Services\UserNotificationService;
use Pivel\Hydro2\Views\EmailViews\Identity\NewUserVerificationEmailView;
use Pivel\Hydro2\Views\Identity\LoginView;

#[RoutePrefix('api/hydro2/identity')]
class SessionController extends BaseController
{
    private ILoggerService $_logger;
    private IdentityService $_identityService;
    private UserNotificationService $_userNotificationService;

    public function __construct(
        ILoggerService $logger,
        IdentityService $identityService,
        UserNotificationService $userNotificationService,
        Request $request,
    )
    {
        $this->_logger = $logger;
        $this->_identityService = $identityService;
        parent::__construct($request);
    }

    #[Route(Method::POST, 'login')]
    #[Route(Method::POST, '~login')]
    #[Route(Method::POST, '~api/login')]
    public function Login(): Response
    {
        if ($this->_identityService->GetSessionFromRequest($this->request) !== null) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'email',
                            'description' => "User's email address",
                            'message' => 'Already logged in.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are invalid.'
            );
        }

        if (!isset($this->request->Args['email'])) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'email',
                            'description' => "User's email address",
                            'message' => "The user's email address is required.",
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are missing.'
            );
        }

        if (!isset($this->request->Args['password'])) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'password',
                            'description' => "User's current password",
                            'message' => "The user's current password is required.",
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are missing.'
            );
        }

        $user = $this->_identityService->GetUserFromEmail($this->request->Args['email']);
        if ($user == null) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'email',
                            'description' => "User's email address",
                            'message' => 'The provided email address does not match an account.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are invalid.'
            );
        }

        if ($user->GetUserRole() === null) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'email',
                            'description' => "User's email address",
                            'message' => 'Unable to log in. Please contact the administrator.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are invalid.'
            );
        }

        if (
            ($user->GetUserRole()->MaxLoginAttempts > 0 && $user->FailedLoginAttempts >= $user->GetUserRole()->MaxLoginAttempts) ||
            ($user->GetUserRole()->Max2FAAttempts > 0 && $user->Failed2FAAttempts >= $user->GetUserRole()->Max2FAAttempts)
        ) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'email',
                            'description' => "User's email address",
                            'message' => 'This account is locked due to too many failed login attempts.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are invalid.'
            );
        }

        if ($user->GetPasswordCount() == 0 || !$user->EmailVerified) {
            $user->EmailVerified = false;
            $this->_identityService->UpdateUser($user);
            $view = new NewUserVerificationEmailView($this->_identityService->GetEmailVerificationUrl($this->request, $user, true), $user->Name);
            $this->_userNotificationService->SendEmailToUser($user, $view);
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'password',
                            'description' => "User's current password",
                            'message' => 'Account creation is incomplete. A validation email has been re-sent to your email address.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are invalid.'
            );
        }
        
        if (!$user->CheckPassword($this->request->Args['password'])) {
            $user->FailedLoginAttempts++;
            $this->_identityService->UpdateUser($user);
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

        if (!$user->Enabled || $user->NeedsReview) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'email',
                            'description' => "User's email address",
                            'message' => 'This account is locked.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are invalid.'
            );
        }

        // reset failed login attempts
        $user->FailedLoginAttempts = 0;
        $this->_identityService->UpdateUser($user);

        $session = $this->_identityService->StartSession($user, $this->request);

        if ($session === null) {
            return new JsonResponse(
                status: StatusCode::InternalServerError,
                error_message: "There was a problem with the database."
            );
        }

        setcookie('sridkey', $session->RandomId . ';' . $session->Key, $session->ExpireTime->getTimestamp(), '/', httponly: true);

        return new JsonResponse(
            data: [
                'login_result' => [
                    'authenticated' => true,
                    'challenge_required' => ($user->GetUserRole()->ChallengeIntervalMinutes>0),
                    'password_change_required' => $user->IsPasswordChangeRequired(),
                ],
            ],
            status: StatusCode::OK,
        );
    }

    #[Route(Method::GET, 'users/{id}/sessions')]
    public function UserGetSessions(): Response
    {
        // need to have either viewusersessions permission or be requestion own user's sessions
        $requestUser = $this->_identityService->GetUserFromRequestOrVisitor($this->request);
        if (!(
            $requestUser->GetUserRole()->HasPermission(Permissions::ViewUserSessions->value) ||
            $requestUser->RandomId == $this->request->Args['id']
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
                            'description' => "User's random ID",
                            'message' => "The user doesn't exist.",
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are invalid.'
            );
        }

        $currentSessionId = $this->_identityService->GetSessionFromRequest($this->request)->RandomId;
        $sessionsResults = [];
        $sessions = $user->GetSessions();
        foreach ($sessions as $s) {
            $sessionsResults[] = [
                'random_id' => $s->RandomId,
                'browser' => $s->Browser,
                'start' => $s->StartTime,
                'expire' => $s->ExpireTime,
                'last_access' => $s->LastAccessTime,
                'start_ip' => $s->StartIP,
                'last_ip' => $s->LastIP,
                'is_this_session' => $s->RandomId === $currentSessionId,
            ];
        }

        return new JsonResponse(
            data: [
                'sessions' => $sessionsResults,
            ],
        );
    }

    #[Route(Method::GET, 'users/{userid}/sessions/{sessionid}/expire')]
    #[Route(Method::POST, 'users/{userid}/sessions/{sessionid}/expire')]
    #[Route(Method::DELETE, 'users/{userid}/sessions/{sessionid}')]
    #[Route(Method::GET, 'sessions/{sessionid}/expire')]
    #[Route(Method::POST, 'sessions/{sessionid}/expire')]
    #[Route(Method::DELETE, 'sessions/{sessionid}')]
    public function UserExpireSession(): Response
    {
        $session = $this->_identityService->GetSessionFromRandomId($this->request->Args['sessionid']);
        // need to have either endusersessions permission or be requesting on own user's sessions
        $requestUser = $this->_identityService->GetUserFromRequestOrVisitor($this->request);
        if (!(
            $requestUser->GetUserRole()->HasPermission(Permissions::EndUserSessions->value) ||
            ($session !== null && $requestUser->Id == $session->GetUser()->Id)
        )) {
            return new Response(status: StatusCode::NotFound);
        }

        if ($session === null) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'sessionid',
                            'description' => "Session's random ID",
                            'message' => "The session doesn't exist.",
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are invalid.'
            );
        }

        if (!$this->_identityService->ExpireSession($session)) {
            return new JsonResponse(
                status: StatusCode::InternalServerError,
                error_message: "There was a problem with the database."
            );
        }

        return new JsonResponse(
            data: [
                'expire_session_result' => true,
            ]
        );
    }

    #[Route(Method::GET, '~login')]
    public function GetLoginView(): Response
    {
        $session = $this->_identityService->GetSessionFromRequest($this->request);
        $requestUser = $this->_identityService->GetUserFromRequestOrVisitor($this->request);
        // check if already logged in. If there is a ?next= arg, redirect to that path. Otherwise, redirect to ~/
        //  unless password change is required, then display the password change screen.
        //  TODO if 2FA challenge is required, then display the 2FA challenge screen.
        $view = new LoginView();
        if ($session !== null) {
            if (!$requestUser->IsPasswordChangeRequired()) {
                return new Response(
                    status: StatusCode::Found,
                    headers: [
                        'Location' => $this->request->Args['next']??'/',
                    ],
                );
            }

            $view->DefaultPage = 'changepassword';
        }

        return new Response(
            content: $view->Render()
        );
    }

    #[Route(Method::GET, '~logout')]
    public function Logout(): Response
    {
        $session = $this->_identityService->GetSessionFromRequest($this->request);

        if ($session !== null) {
            $this->_identityService->ExpireSession($session);
            setcookie('sridkey', '', time()-3600, '/');
        }

        return new Response(
            status: StatusCode::Found,
            headers: [
                'Location' => '/login',
            ],
        );
    }
}