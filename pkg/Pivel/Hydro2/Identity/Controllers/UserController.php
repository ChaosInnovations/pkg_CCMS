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
use Package\Pivel\Hydro2\Identity\Extensions\Permissions;
use Package\Pivel\Hydro2\Identity\Models\User;
use Package\Pivel\Hydro2\Identity\Services\IdentityService;

#[RoutePrefix('api/hydro2/core/identity/users')]
class IdentityController extends BaseController
{
    #[Route(Method::GET, '')]
    public function ListUsers() : Response {
        // if current user doesn't have permission pivel/hydro2/viewusers/, return 404
        if (!DatabaseService::IsPrimaryConnected()) {
            return new Response(status: StatusCode::NotFound);
        }
        if (!IdentityService::GetRequestUser($this->request)->role->HasPermission(Permissions::ViewUsers->value)) {
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

    // TODO: Implement CreateUser
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
        if (!DatabaseService::IsPrimaryConnected()) {
            return new Response(status: StatusCode::NotFound);
        }
        // if current user doesn't have permission pivel/hydro2/viewusers/, return 404,
        //  unless trying to get info about self which is always allowed
        if (!(
            IdentityService::GetRequestUser($this->request)->role->HasPermission(Permissions::ViewUsers->value) ||
            IdentityService::GetRequestUser($this->request)->RandomId === ($this->request->Args['id']??null)
            )) {
            return new Response(status: StatusCode::NotFound);
        }

        if (!isset($this->request->Args['id'])) {
            // missing argument
            // return response with code 400 (Bad Request)
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'id',
                            'description' => 'User ID',
                            'message' => 'Argument is missing.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are missing.'
            );
        }

        $user = User::LoadFromRandomId($this->request->Args['id']);

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

    // TODO Implement UpdateUser
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
        if (!DatabaseService::IsPrimaryConnected()) {
            return new Response(status: StatusCode::NotFound);
        }
        // if current user doesn't have permission pivel/hydro2/manageusers/, return 404
        if (!IdentityService::GetRequestUser($this->request)->role->HasPermission(Permissions::ManageUsers->value)) {
            return new Response(status: StatusCode::NotFound);
        }

        if (!isset($this->request->Args['id'])) {
            // missing argument
            // return response with code 400 (Bad Request)
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'id',
                            'description' => 'User ID',
                            'message' => 'Argument is missing.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are missing.'
            );
        }

        $user = User::LoadFromRandomId($this->request->Args['id']);

        if ($user != null && !$user->Delete()) {
            return new JsonResponse(
                null,
                StatusCode::InternalServerError,
                "There was a problem with the database."
            );
        }

        return new JsonResponse(status:StatusCode::OK);
    }

    // TODO Implement UserChangePassword
    #[Route(Method::POST, '{id}/changepassword')]
    public function UserChangePassword() : Response {
        return new JsonResponse(
            status:StatusCode::InternalServerError,
            error_message:'Route exists but not implemented.',
        );
    }

    // TODO Implement UserResetPassword
    #[Route(Method::POST, '{id}/resetpassword')]
    public function UserResetPassword() : Response {
        return new JsonResponse(
            status:StatusCode::InternalServerError,
            error_message:'Route exists but not implemented.',
        );
    }
}