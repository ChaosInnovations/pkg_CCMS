<?php

namespace Package\Pivel\Hydro2\Identity\Controllers;

use DateTime;
use DateTimeZone;
use Package\Pivel\Hydro2\Core\Controllers\BaseController;
use Package\Pivel\Hydro2\Core\Extensions\Route;
use Package\Pivel\Hydro2\Core\Extensions\RoutePrefix;
use Package\Pivel\Hydro2\Core\Models\HTTP\Method;
use Package\Pivel\Hydro2\Core\Models\HTTP\StatusCode;
use Package\Pivel\Hydro2\Core\Models\JsonResponse;
use Package\Pivel\Hydro2\Core\Models\Response;
use Package\Pivel\Hydro2\Identity\Models\Session;
use Package\Pivel\Hydro2\Identity\Models\User;
use Package\Pivel\Hydro2\Identity\Models\UserPassword;
use Package\Pivel\Hydro2\Identity\Services\IdentityService;
use Package\Pivel\Hydro2\Identity\Views\EmailViews\NewUserVerificationEmailView;
use Package\Pivel\Hydro2\Identity\Views\LoginView;

// TODO Implement these routes
#[RoutePrefix('api/hydro2/identity')]
class SessionController extends BaseController
{
    #[Route(Method::POST, 'login')]
    #[Route(Method::POST, '~login')]
    #[Route(Method::POST, '~admin')]
    #[Route(Method::POST, '~api/login')]
    public function Login() : Response {
        if (IdentityService::GetRequestSession($this->request) !== false) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'email',
                            'description' => 'User\'s email address',
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
                            'description' => 'User\'s email address',
                            'message' => 'The user\'s email address is required.',
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
                            'description' => 'User\'s current password',
                            'message' => 'The user\'s current password is required.',
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
                            'description' => 'User\'s email address',
                            'message' => 'The provided email address does not match an account.',
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
            $view = new NewUserVerificationEmailView(IdentityService::GetEmailVerificationUrl($this->request, $user, true), $user->Name);
            IdentityService::SendEmailToUser($user, $view);
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'password',
                            'description' => 'User\'s current password',
                            'message' => 'Account creation is incomplete. A validation email has been re-sent to your email address.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are invalid.'
            );
        }

        if (!$userPassword->ComparePassword($this->request->Args['password'])) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'password',
                            'description' => 'User\'s current password',
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
                            'description' => 'User\'s email address',
                            'message' => 'This account is locked.',
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
                            'description' => 'User\'s email address',
                            'message' => 'This account is locked due to too many failed login attempts.',
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
        return new JsonResponse(
            status:StatusCode::InternalServerError,
            error_message:'Route exists but not implemented.',
        );
    }

    #[Route(Method::GET, 'users/{userid}/sessions/{sessionid}/expire')]
    #[Route(Method::POST, 'users/{userid}/sessions/{sessionid}/expire')]
    #[Route(Method::DELETE, 'users/{userid}/sessions/{sessionid}')]
    #[Route(Method::GET, 'sessions/{sessionid}/expire')]
    #[Route(Method::POST, 'sessions/{sessionid}/expire')]
    #[Route(Method::DELETE, 'sessions/{sessionid}')]
    public function UserExpireSession() : Response {
        return new JsonResponse(
            status:StatusCode::InternalServerError,
            error_message:'Route exists but not implemented.',
        );
    }

    #[Route(Method::GET, '~login')]
    #[Route(Method::GET, '~admin')]
    public function GetLoginView() : Response {
        // TODO check if already logged in. If there is a ?next= arg, redirect to that path. Otherwise, redirect to ~loggedin
        // TODO ~admin should be a separate route that also displays the admin panel and redirects here if not logged in.
        $view = new LoginView();
        return new Response(
            content: $view->Render(),
            status: StatusCode::OK
        );
    }
}