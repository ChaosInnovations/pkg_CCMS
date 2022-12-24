<?php

namespace Package\Pivel\Hydro2\Identity\Controllers;

use Package\Pivel\Hydro2\Core\Controllers\BaseController;
use Package\Pivel\Hydro2\Core\Extensions\Route;
use Package\Pivel\Hydro2\Core\Extensions\RoutePrefix;
use Package\Pivel\Hydro2\Core\Models\HTTP\Method;
use Package\Pivel\Hydro2\Core\Models\HTTP\StatusCode;
use Package\Pivel\Hydro2\Core\Models\JsonResponse;
use Package\Pivel\Hydro2\Core\Models\Response;

#[RoutePrefix('api/hydro2/core/identity/userroles')]
class IdentityController extends BaseController
{
    #[Route(Method::GET, '')]
    public function GetUserRoles() : Response {
        return new JsonResponse(
            status:StatusCode::InternalServerError,
            error_message:'Route exists but not implemented.',
        );
    }

    #[Route(Method::POST, '')]
    #[Route(Method::POST, 'create')]
    public function CreateUserRole() : Response {
        return new JsonResponse(
            status:StatusCode::InternalServerError,
            error_message:'Route exists but not implemented.',
        );
    }

    #[Route(Method::GET, '{id}')]
    public function GetUserRole() : Response {
        return new JsonResponse(
            status:StatusCode::InternalServerError,
            error_message:'Route exists but not implemented.',
        );
    }

    #[Route(Method::GET, '{id}/users')]
    #[Route(Method::POST, '{id}/users')]
    public function GetUsersWithRole() : Response {
        return new JsonResponse(
            status:StatusCode::InternalServerError,
            error_message:'Route exists but not implemented.',
        );
    }

    #[Route(Method::GET, '{id}/update')]
    #[Route(Method::POST, '{id}/update')]
    #[Route(Method::POST, '{id}')]
    public function UpdateUserRole() : Response {
        return new JsonResponse(
            status:StatusCode::InternalServerError,
            error_message:'Route exists but not implemented.',
        );
    }

    #[Route(Method::GET, '{id}/remove')]
    #[Route(Method::POST, '{id}/remove')]
    #[Route(Method::DELETE, '{id}')]
    public function DeleteUserRole() : Response {
        return new JsonResponse(
            status:StatusCode::InternalServerError,
            error_message:'Route exists but not implemented.',
        );
    }

    #[Route(Method::GET, '~api/hydro2/core/identity/permissions')]
    public function GetPermissions() : Response {
        return new JsonResponse(
            status:StatusCode::InternalServerError,
            error_message:'Route exists but not implemented.',
        );
    }
}