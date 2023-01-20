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
use Package\Pivel\Hydro2\Identity\Models\Permissions;
use Package\Pivel\Hydro2\Identity\Models\UserPermission;
use Package\Pivel\Hydro2\Identity\Models\UserRole;
use Package\Pivel\Hydro2\Identity\Services\IdentityService;

// TODO Implement these routes
#[RoutePrefix('api/hydro2/identity/userroles')]
class UserRoleController extends BaseController
{
    #[Route(Method::GET, '')]
    public function GetUserRoles() : Response {
        // if current user doesn't have permission pivel/hydro2/viewusers/, return 404
        if (!DatabaseService::IsPrimaryConnected()) {
            return new Response(status: StatusCode::NotFound);
        }
        if (!(
            IdentityService::GetRequestUser($this->request)->Role->HasPermission(Permissions::ManageUserRoles->value) ||
            IdentityService::GetRequestUser($this->request)->Role->HasPermission(Permissions::CreateUsers->value) ||
            IdentityService::GetRequestUser($this->request)->Role->HasPermission(Permissions::ManageUsers->value)
        )) {
            return new Response(status: StatusCode::NotFound);
        }

        /** @var UserRole[] */
        $userRoles = UserRole::GetAll();

        $userRoleResults = [];

        $permissions = IdentityService::GetAvailablePermissions();

        foreach ($userRoles as $userRole) {
            $userRoleResults[] = [
                'id' => $userRole->Id,
                'name' => $userRole->Name,
                'description' => $userRole->Description,
                'max_login_attempts' => $userRole->MaxLoginAttempts,
                'max_session_length' => $userRole->MaxSessionLengthMinutes,
                'max_password_age' => $userRole->MaxPasswordAgeDays,
                'days_until_2fa_setup_required' => $userRole->DaysUntil2FASetupRequired,
                'challenge_interval' => $userRole->ChallengeIntervalMinutes,
                'max_2fa_attempts' => $userRole->Max2FAAttempts,
                'permissions' => array_map(function ($p) use ($permissions) {
                    /** @var UserPermission $p */
                    return [
                        'key' => $p->PermissionKey,
                        'name' => isset($permissions[$p->PermissionKey])?$permissions[$p->PermissionKey]->Name:'Unknown Permission',
                    ];
                }, $userRole->Permissions),
            ];
        }

        return new JsonResponse(
            data:[
                'user_roles' => $userRoleResults,
            ],
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