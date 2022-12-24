<?php

namespace Package\Pivel\Hydro2\Identity\Controllers;

use Package\Pivel\Hydro2\Core\Controllers\BaseController;
use Package\Pivel\Hydro2\Core\Extensions\Route;
use Package\Pivel\Hydro2\Core\Extensions\RoutePrefix;
use Package\Pivel\Hydro2\Core\Models\HTTP\Method;
use Package\Pivel\Hydro2\Core\Models\HTTP\StatusCode;
use Package\Pivel\Hydro2\Core\Models\JsonResponse;
use Package\Pivel\Hydro2\Core\Models\Response;

#[RoutePrefix('api/hydro2/core/identity/users')]
class IdentityController extends BaseController
{
    #[Route(Method::GET, '')]
    public function ListUsers() : Response {
        return new JsonResponse(
            status:StatusCode::InternalServerError,
            error_message:'Route exists but not implemented.',
        );
    }

    #[Route(Method::POST, '')]
    #[Route(Method::POST, 'create')]
    public function CreateUser() : Response {
        return new JsonResponse(
            status:StatusCode::InternalServerError,
            error_message:'Route exists but not implemented.',
        );
    }

    #[Route(Method::GET, '{id}')]
    public function ListUser() : Response {
        return new JsonResponse(
            status:StatusCode::InternalServerError,
            error_message:'Route exists but not implemented.',
        );
    }

    #[Route(Method::POST, '{id}')]
    #[Route(Method::POST, '{id}/update')]
    public function UpdateUser() : Response {
        return new JsonResponse(
            status:StatusCode::InternalServerError,
            error_message:'Route exists but not implemented.',
        );
    }

    #[Route(Method::POST, '{id}/remove')]
    #[Route(Method::DELETE, '{id}/remove')]
    public function DeleteUser() : Response {
        return new JsonResponse(
            status:StatusCode::InternalServerError,
            error_message:'Route exists but not implemented.',
        );
    }

    #[Route(Method::POST, '{id}/changepassword')]
    public function UserChangePassword() : Response {
        return new JsonResponse(
            status:StatusCode::InternalServerError,
            error_message:'Route exists but not implemented.',
        );
    }

    #[Route(Method::POST, '{id}/resetpassword')]
    public function UserResetPassword() : Response {
        return new JsonResponse(
            status:StatusCode::InternalServerError,
            error_message:'Route exists but not implemented.',
        );
    }
}