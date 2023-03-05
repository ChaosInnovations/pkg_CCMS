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
use Package\Pivel\Hydro2\Identity\Models\User;
use Package\Pivel\Hydro2\Identity\Models\UserPermission;
use Package\Pivel\Hydro2\Identity\Models\UserRole;
use Package\Pivel\Hydro2\Identity\Services\IdentityService;

#[RoutePrefix('api/hydro2/identity/userroles')]
class UserRoleController extends BaseController
{
    #[Route(Method::GET, '')]
    public function GetUserRoles() : Response {
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
        if (!DatabaseService::IsPrimaryConnected()) {
            return new Response(status: StatusCode::NotFound);
        }
        if (!IdentityService::GetRequestUser($this->request)->Role->HasPermission(Permissions::CreateUserRoles->value)) {
            return new Response(status: StatusCode::NotFound);
        }

        $userRole = new UserRole(
            name: $this->request->Args['name']??'New Role',
            description: $this->request->Args['description']??'',
            maxLoginAttempts: intval($this->request->Args['max_login_attempts']??5),
            maxSessionLengthMinutes: intval($this->request->Args['max_session_length_minutes']??43200),
            daysUntil2FASetupRequired: intval($this->request->Args['days_until_2fa_setup_required']??3),
            challengeIntervalMinutes: intval($this->request->Args['challenge_interval']??21600),
            max2FAAttempts: intval($this->request->Args['max_2fa_attempts']??5),
        );

        $requestedPermissions = $this->request->Args['permissions']??[];
        $availablePermissions = IdentityService::GetAvailablePermissions();

        // validate permissions. can't add yet since we don't know that the new UserRole's ID will be.
        foreach ($requestedPermissions as $permission) {
            if (!isset($availablePermissions[$permission])) {
                return new JsonResponse(
                    data: [
                        'validation_errors' => [
                            [
                                'name' => 'permissions',
                                'description' => 'User Role\'s permissions',
                                'message' => "The permission '{$permission}' doesn\'t exist.",
                            ],
                        ],
                    ],
                    status: StatusCode::BadRequest,
                    error_message: 'One or more arguments are invalid.'
                );
            }
        }

        if (!$userRole->Save()) {
            return new JsonResponse(
                status: StatusCode::InternalServerError,
                error_message: "There was a problem with the database."
            );
        }

        // now we can add the permission.

        foreach ($requestedPermissions as $permission) {
            echo $permission."\n";
            if (!$userRole->AddPermission($permission)) {
                return new JsonResponse(
                    status: StatusCode::InternalServerError,
                    error_message: 'The UserRole was generated, but there was a problem with the database while adding permissions.'
                );
            }
        }

        return new JsonResponse(
            status:StatusCode::OK
        );
    }

    #[Route(Method::GET, '{id}')]
    public function GetUserRole() : Response {
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

        /** @var UserRole */
        $userRole = UserRole::LoadFromId($this->request->Args['id']);

        if ($userRole === null) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'id',
                            'description' => 'User Role ID.',
                            'message' => 'This user role doesn\'t exist.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are invalid.'
            );
        }

        $permissions = IdentityService::GetAvailablePermissions();

        $userRoleResults = [[
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
                    'name' => (isset($permissions[$p->PermissionKey])?$permissions[$p->PermissionKey]->Name:'Unknown Permission'),
                ];
            }, $userRole->Permissions),
        ]];

        return new JsonResponse(
            data:[
                'user_roles' => $userRoleResults,
            ],
        );
    }

    #[Route(Method::POST, '{id}/update')]
    #[Route(Method::POST, '{id}')]
    public function UpdateUserRole() : Response {
        if (!DatabaseService::IsPrimaryConnected()) {
            return new Response(status: StatusCode::NotFound);
        }
        if (!IdentityService::GetRequestUser($this->request)->Role->HasPermission(Permissions::ManageUserRoles->value)) {
            return new Response(status: StatusCode::NotFound);
        }

        /** @var UserRole */
        $userRole = UserRole::LoadFromId($this->request->Args['id']);

        if ($userRole === null) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'id',
                            'description' => 'User Role ID.',
                            'message' => 'This user role doesn\'t exist.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are invalid.'
            );
        }

        $userRole->Name = $this->request->Args['name']??$userRole->Name;
        $userRole->Description = $this->request->Args['description']??$userRole->Description;
        $userRole->MaxLoginAttempts = intval($this->request->Args['max_login_attempts']??$userRole->MaxLoginAttempts);
        $userRole->MaxSessionLengthMinutes = intval($this->request->Args['max_session_length_minutes']??$userRole->MaxSessionLengthMinutes);
        $userRole->DaysUntil2FASetupRequired = intval($this->request->Args['days_until_2fa_setup_required']??$userRole->DaysUntil2FASetupRequired);
        $userRole->ChallengeIntervalMinutes = intval($this->request->Args['challenge_interval']??$userRole->ChallengeIntervalMinutes);
        $userRole->Max2FAAttempts = intval($this->request->Args['max_2fa_attempts']??$userRole->Max2FAAttempts);

        $requestedPermissionKeys = $this->request->Args['permissions']??[];
        $availablePermissions = IdentityService::GetAvailablePermissions();

        // validate new permissions
        foreach ($requestedPermissionKeys as $permissionKey) {
            if (!isset($availablePermissions[$permissionKey])) {
                return new JsonResponse(
                    data: [
                        'validation_errors' => [
                            [
                                'name' => 'permissions',
                                'description' => 'User Role\'s permissions',
                                'message' => "The permission '{$permissionKey}' doesn\'t exist.",
                            ],
                        ],
                    ],
                    status: StatusCode::BadRequest,
                    error_message: 'One or more arguments are invalid.'
                );
            }
        }

        if (!$userRole->Save()) {
            return new JsonResponse(
                status: StatusCode::InternalServerError,
                error_message: "There was a problem with the database."
            );
        }

        // add new permissions
        foreach ($requestedPermissionKeys as $permissionKey) {
            if (!$userRole->AddPermission($permissionKey)) {
                return new JsonResponse(
                    status: StatusCode::InternalServerError,
                    error_message: 'The UserRole was updated, but there was a problem with the database while adding permissions.'
                );
            }
        }
        // remove permissions
        foreach ($userRole->Permissions as $permission) {
            if (in_array($permission->PermissionKey, $requestedPermissionKeys)) {
                continue;
            }
            if (!$userRole->RemovePermission($permission->PermissionKey)) {
                return new JsonResponse(
                    status: StatusCode::InternalServerError,
                    error_message: 'The UserRole was updated, but there was a problem with the database while removing permissions.'
                );
            }
        }

        

        return new JsonResponse(
            status:StatusCode::OK
        );
    }

    #[Route(Method::GET, '{id}/remove')]
    #[Route(Method::DELETE, '{id}')]
    public function DeleteUserRole() : Response {
        if (!DatabaseService::IsPrimaryConnected()) {
            return new Response(status: StatusCode::NotFound);
        }
        if (!IdentityService::GetRequestUser($this->request)->Role->HasPermission(Permissions::ManageUserRoles->value)) {
            return new Response(status: StatusCode::NotFound);
        }

        /** @var UserRole */
        $userRole = UserRole::LoadFromId($this->request->Args['id']);

        if ($userRole != null && count(User::GetAllWithRole($userRole)) != 0) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'id',
                            'description' => 'User Role ID.',
                            'message' => 'Cannot delete a role while there are users with this role.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are invalid.',
            );
        }

        if ($userRole != null && !$userRole->Delete()) {
            return new JsonResponse(
                status: StatusCode::InternalServerError,
                error_message: "There was a problem with the database.",
            );
        }

        return new JsonResponse(status:StatusCode::OK);
    }

    #[Route(Method::GET, '~api/hydro2/identity/permissions')]
    public function GetPermissions() : Response {
        if (!DatabaseService::IsPrimaryConnected()) {
            return new Response(status: StatusCode::NotFound);
        }
        if (!(
            IdentityService::GetRequestUser($this->request)->Role->HasPermission(Permissions::ManageUserRoles->value) ||
            IdentityService::GetRequestUser($this->request)->Role->HasPermission(Permissions::CreateUserRoles->value)
        )) {
            return new Response(status: StatusCode::NotFound);
        }

        $permissions = IdentityService::GetAvailablePermissions();
        $permissionResults = [];

        foreach ($permissions as $permission) {
            $permissionResults[] = [
                'vendor' => $permission->Vendor,
                'package' => $permission->Package,
                'key' => $permission->Key,
                'name' => $permission->Name,
                'description' => $permission->Description,
                'requires' => $permission->Requires,
            ];
        }

        return new JsonResponse(
            data:[
                'permissions' => $permissionResults,
            ],
        );
    }
}