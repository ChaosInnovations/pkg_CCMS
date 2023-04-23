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
use Pivel\Hydro2\Views\EmailViews\Identity\NewUserVerificationEmailView;
use Pivel\Hydro2\Views\Identity\LoginView;

#[RoutePrefix('api/hydro2/identity')]
class SessionController extends BaseController
{
    protected IdentityService $_identityService;
    protected DatabaseService $_databaseService;

    public function __construct(
        IdentityService $identityService,
        DatabaseService $databaseService,
        Request $request,
    )
    {
        $this->_identityService = $identityService;
        parent::__construct($request);
    }

    #[Route(Method::POST, 'login')]
    #[Route(Method::POST, '~login')]
    #[Route(Method::POST, '~api/login')]
    public function Login() : Response {
        if ($this->_identityService->GetRequestSession($this->request) !== false) {
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

        $user = User::LoadFromEmail($this->request->Args['email']);
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

        if (
            ($user->Role->MaxLoginAttempts > 0 && $user->FailedLoginAttempts >= $user->Role->MaxLoginAttempts) ||
            ($user->Role->Max2FAAttempts > 0 && $user->Failed2FAAttempts >= $user->Role->Max2FAAttempts)
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

        $userPassword = UserPassword::LoadCurrentFromUser($user);
        if ($userPassword === null || !$user->EmailVerified) {
            $user->EmailVerified = false;
            $user->Save();
            $view = new NewUserVerificationEmailView($this->_identityService->GetEmailVerificationUrl($this->request, $user, true), $user->Name);
            $this->_identityService->SendEmailToUser($user, $view);
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

        if (!$userPassword->ComparePassword($this->request->Args['password'])) {
            $user->FailedLoginAttempts++;
            $user->Save();
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

        $sessionStarts = new DateTime(timezone:new DateTimeZone('UTC'));
        $sessionExpires = (clone $sessionStarts)->modify("+{$user->Role->MaxSessionLengthMinutes} minutes");
        $session2FAExpires = $user->Role->ChallengeIntervalMinutes>0?(clone $sessionStarts):null;

        // create new session
        $session = new Session(
            userId: $user->Id,
            browser: $this->request->UserAgent,
            startTime: $sessionStarts,
            expireTime: $sessionExpires,
            expire2FATime: $session2FAExpires,
            lastAccessTime: $sessionStarts,
            startIP: $this->request->getClientAddress(),
            lastIP: $this->request->getClientAddress(),
        );

        if (!$session->Save()) {
            return new JsonResponse(
                status: StatusCode::InternalServerError,
                error_message: "There was a problem with the database."
            );
        }

        setcookie('sridkey', $session->RandomId . ';' . $session->Key, $sessionExpires->getTimestamp(), '/', httponly: true);

        return new JsonResponse(
            data: [
                'login_result' => [
                    'authenticated' => true,
                    'challenge_required' => ($user->Role->ChallengeIntervalMinutes>0),
                    'password_change_required' => $userPassword->IsExpired(),
                ],
            ],
            status: StatusCode::OK,
        );
    }

    #[Route(Method::GET, 'users/{id}/sessions')]
    public function UserGetSessions() : Response {
        if (!$this->_databaseService->IsPrimaryConnected()) {
            return new Response(status: StatusCode::NotFound);
        }
        // need to have either viewusersessions permission or be requestion own user's sessions
        if (!(
            $this->_identityService->GetRequestUser($this->request)->Role->HasPermission(Permissions::ViewUserSessions->value) ||
            $this->_identityService->GetRequestUser($this->request)->RandomId === $this->request->Args['id']
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
                            'description' => "User's random ID",
                            'message' => "The user doesn't exist.",
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are invalid.'
            );
        }

        $currentSessionId = $this->_identityService->GetRequestSession($this->request)->RandomId;
        $sessionsResults = [];
        $sessions = Session::GetAllByUser($user);
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
    public function UserExpireSession() : Response {
        if (!$this->_databaseService->IsPrimaryConnected()) {
            return new Response(status: StatusCode::NotFound);
        }
        // need to have either viewusersessions permission or be requesting on own user's session
        $session = Session::LoadFromRandomId($this->request->Args['sessionid']);
        if (!(
            $this->_identityService->GetRequestUser($this->request)->Role->HasPermission(Permissions::EndUserSessions->value) ||
            
            (
                $session !== null &&
                $this->_identityService->GetRequestUser($this->request)->Id === $session->UserId
            )
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

        $session->Expire();
        if (!$session->Save()) {
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
    public function GetLoginView() : Response {
        // check if already logged in. If there is a ?next= arg, redirect to that path. Otherwise, redirect to ~/
        //  unless password change is required, then display the password change screen.
        //  TODO if 2FA challenge is required, then display the 2FA challenge screen.
        if ($this->_identityService->GetRequestSession($this->request) !== false) {
            $userPassword = UserPassword::LoadCurrentFromUser($this->_identityService->GetRequestUser($this->request));
            if (!$userPassword->IsExpired()) {
                return new Response(
                    status: StatusCode::Found,
                    headers: [
                        'Location' => $this->request->Args['next']??'/',
                    ],
                );
            }
        }

        $view = new LoginView();
        if ($this->_identityService->GetRequestSession($this->request) !== false && $userPassword->IsExpired()) {
            $view->DefaultPage = 'changepassword';
        }
        return new Response(
            content: $view->Render()
        );
    }

    #[Route(Method::GET, '~logout')]
    public function Logout() : Response {
        if ($this->_identityService->GetRequestSession($this->request) !== false) {
            $this->_identityService->GetRequestSession($this->request)->Expire();
            $this->_identityService->GetRequestSession($this->request)->Save();
        }

        return new Response(
            status: StatusCode::Found,
            headers: [
                'Location' => '/login',
            ],
        );
    }
}