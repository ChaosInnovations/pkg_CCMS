<?php

namespace Package\Pivel\Hydro2\Identity\Controllers;

use Package\Pivel\Hydro2\Core\Controllers\BaseController;
use Package\Pivel\Hydro2\Core\Extensions\Route;
use Package\Pivel\Hydro2\Core\Extensions\RoutePrefix;
use Package\Pivel\Hydro2\Core\Models\HTTP\Method;
use Package\Pivel\Hydro2\Core\Models\HTTP\StatusCode;
use Package\Pivel\Hydro2\Core\Models\JsonResponse;
use Package\Pivel\Hydro2\Core\Models\Response;

// TODO Implement these routes
#[RoutePrefix('api/hydro2/core/identity')]
class IdentityController extends BaseController
{
    #[Route(Method::POST, 'login')]
    #[Route(Method::POST, '~api/login')]
    public function Login() : Response {
        return new JsonResponse(
            status:StatusCode::InternalServerError,
            error_message:'Route exists but not implemented.',
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
}