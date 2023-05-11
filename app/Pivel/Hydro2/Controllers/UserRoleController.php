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
use Pivel\Hydro2\Models\Identity\UserRole;
use Pivel\Hydro2\Models\Permissions;
use Pivel\Hydro2\Services\Identity\IIdentityService;

use function PHPUnit\Framework\isEmpty;

#[RoutePrefix('api/hydro2/identity/userroles')]
class UserRoleController extends BaseController
{
    protected IIdentityService $_identityService;

    public function __construct(
        IIdentityService $identityService,
        Request $request,
    )
    {
        $this->_identityService = $identityService;
        parent::__construct($request);
    }
    
    #[Route(Method::GET, '')]
    public function GetUserRoles(): Response
    {
        // if current user doesn't have permission , return 404
        $requestUser = $this->_identityService->GetUserFromRequestOrVisitor($this->request);
        if (!(
            $requestUser->GetUserRole()->HasPermission(Permissions::ManageUserRoles->value) ||
            $requestUser->GetUserRole()->HasPermission(Permissions::CreateUsers->value) ||
            $requestUser->GetUserRole()->HasPermission(Permissions::ManageUsers->value)
        )) {
            return new Response(status: StatusCode::NotFound);
        }

        $query = new Query();
        $query->Limit($this->request->Args['limit'] ?? -1);
        $query->Offset($this->request->Args['offset'] ?? 0);
        
        if (isset($this->request->Args['sort_by'])) {
            $dir = Order::tryFrom(strtoupper($this->request->Args['sort_dir']??'asc'))??Order::Ascending;
            $query->OrderBy($this->request->Args['sort_by']??'id', $dir);
        }

        if (isset($this->request->Args['q']) && !empty($this->request->Args['q'])) {
            $query->Like('name', '%' . str_replace('%', '\\%', str_replace('_', '\\_', $this->request->Args['q'])) . '%');
        }

        $userRoles = $this->_identityService->GetUserRolesMatchingQuery($query);
        $permissions = $this->_identityService->GetAvailablePermissions();

        $userRoleResults = [];
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
                        'name' => isset($permissions[$p->PermissionKey]) ? $permissions[$p->PermissionKey]->Name : 'Unknown Permission',
                    ];
                }, $userRole->GetPermissions()),
            ];
        }

        return new JsonResponse(
            data:[
                'user_roles' => $userRoleResults,
            ],
        );
    }

    #[Route(Method::POST, '')]
    public function CreateUserRole(): Response
    {
        // if current user doesn't have permission , return 404
        $requestUser = $this->_identityService->GetUserFromRequestOrVisitor($this->request);
        if (!$requestUser->GetUserRole()->HasPermission(Permissions::CreateUserRoles->value)) {
            return new Response(status: StatusCode::NotFound);
        }

        $userRole = new UserRole(
            name: $this->request->Args['name'] ?? 'New Role',
            description: $this->request->Args['description'] ?? '',
            maxLoginAttempts: intval($this->request->Args['max_login_attempts'] ?? 5),
            maxSessionLengthMinutes: intval($this->request->Args['max_session_length_minutes'] ?? 43200),
            daysUntil2FASetupRequired: intval($this->request->Args['days_until_2fa_setup_required'] ?? 3),
            challengeIntervalMinutes: intval($this->request->Args['challenge_interval'] ?? 21600),
            max2FAAttempts: intval($this->request->Args['max_2fa_attempts'] ?? 5),
        );

        $requestedPermissions = $this->request->Args['permissions']??[];
        $availablePermissions = $this->_identityService->GetAvailablePermissions();

        // validate permissions. can't add yet since we don't know that the new UserRole's ID will be.
        foreach ($requestedPermissions as $permission) {
            if (!isset($availablePermissions[$permission])) {
                return new JsonResponse(
                    data: [
                        'validation_errors' => [
                            [
                                'name' => 'permissions',
                                'description' => 'User Role\'s permissions',
                                'message' => "The permission '{$permission}' doesn't exist.",
                            ],
                        ],
                    ],
                    status: StatusCode::BadRequest,
                    error_message: 'One or more arguments are invalid.'
                );
            }
        }

        if ($this->_identityService->CreateNewUserRole($userRole) === null) {
            return new JsonResponse(
                status: StatusCode::InternalServerError,
                error_message: "There was a problem with the database."
            );
        }

        // now we can add the permissions.
        foreach ($requestedPermissions as $permission) {
            if (!$userRole->GrantPermission($permission)) {
                return new JsonResponse(
                    status: StatusCode::InternalServerError,
                    error_message: 'The UserRole was generated, but there was a problem with the database while adding permissions.'
                );
            }
        }

        $permissions = $this->_identityService->GetAvailablePermissions();

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
                    'name' => (isset($permissions[$p->PermissionKey]) ? $permissions[$p->PermissionKey]->Name : 'Unknown Permission'),
                ];
            }, $userRole->GetPermissions()),
        ]];

        return new JsonResponse(
            data:[
                'user_roles' => $userRoleResults,
            ],
        );
    }

    #[Route(Method::GET, '{id}')]
    public function GetUserRole(): Response
    {
        // if current user doesn't have permission , return 404
        $requestUser = $this->_identityService->GetUserFromRequestOrVisitor($this->request);
        if (!(
            $requestUser->GetUserRole()->HasPermission(Permissions::ManageUserRoles->value) ||
            $requestUser->GetUserRole()->HasPermission(Permissions::CreateUsers->value) ||
            $requestUser->GetUserRole()->HasPermission(Permissions::ManageUsers->value)
        )) {
            return new Response(status: StatusCode::NotFound);
        }

        $userRole = $this->_identityService->GetUserRoleFromId($this->request->Args['id']);

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

        $permissions = $this->_identityService->GetAvailablePermissions();

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
                    'name' => (isset($permissions[$p->PermissionKey]) ? $permissions[$p->PermissionKey]->Name : 'Unknown Permission'),
                ];
            }, $userRole->GetPermissions()),
        ]];

        return new JsonResponse(
            data:[
                'user_roles' => $userRoleResults,
            ],
        );
    }

    #[Route(Method::POST, '{id}')]
    public function UpdateUserRole(): Response
    {
        // if current user doesn't have permission , return 404
        $requestUser = $this->_identityService->GetUserFromRequestOrVisitor($this->request);
        if (!$requestUser->GetUserRole()->HasPermission(Permissions::ManageUserRoles->value)) {
            return new Response(status: StatusCode::NotFound);
        }

        $userRole = $this->_identityService->GetUserRoleFromId($this->request->Args['id']);

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
        $availablePermissions = $this->_identityService->GetAvailablePermissions();

        // validate new permissions
        foreach ($requestedPermissionKeys as $permissionKey) {
            if (!isset($availablePermissions[$permissionKey])) {
                return new JsonResponse(
                    data: [
                        'validation_errors' => [
                            [
                                'name' => 'permissions',
                                'description' => 'User Role\'s permissions',
                                'message' => "The permission '{$permissionKey}' doesn't exist.",
                            ],
                        ],
                    ],
                    status: StatusCode::BadRequest,
                    error_message: 'One or more arguments are invalid.'
                );
            }
        }

        if (!$this->_identityService->UpdateUserRole($userRole)) {
            return new JsonResponse(
                status: StatusCode::InternalServerError,
                error_message: "There was a problem with the database."
            );
        }

        // add new permissions
        foreach ($requestedPermissionKeys as $permissionKey) {
            if (!$userRole->GrantPermission($permissionKey)) {
                return new JsonResponse(
                    status: StatusCode::InternalServerError,
                    error_message: 'The UserRole was updated, but there was a problem with the database while adding permissions.'
                );
            }
        }
        // remove permissions
        foreach ($userRole->GetPermissions() as $permission) {
            if (in_array($permission->PermissionKey, $requestedPermissionKeys)) {
                continue;
            }
            if (!$userRole->DenyPermission($permission->PermissionKey)) {
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

    #[Route(Method::DELETE, '{id}')]
    public function DeleteUserRole(): Response
    {
        // if current user doesn't have permission , return 404
        $requestUser = $this->_identityService->GetUserFromRequestOrVisitor($this->request);
        if (!$requestUser->GetUserRole()->HasPermission(Permissions::ManageUserRoles->value)) {
            return new Response(status: StatusCode::NotFound);
        }

        $userRole = $this->_identityService->GetUserRoleFromId($this->request->Args['id']);

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

        if ($userRole->GetUserCount() != 0) {
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

        if (!$this->_identityService->DeleteUserRole($userRole)) {
            return new JsonResponse(
                status: StatusCode::InternalServerError,
                error_message: "There was a problem with the database.",
            );
        }

        return new JsonResponse(status:StatusCode::OK);
    }

    #[Route(Method::GET, '~api/hydro2/identity/permissions')]
    public function GetPermissions(): Response
    {
        
        // if current user doesn't have permission , return 404
        $requestUser = $this->_identityService->GetUserFromRequestOrVisitor($this->request);
        if (!(
            $requestUser->GetUserRole()->HasPermission(Permissions::ManageUserRoles->value) ||
            $requestUser->GetUserRole()->HasPermission(Permissions::CreateUserRoles->value)
        )) {
            return new Response(status: StatusCode::NotFound);
        }

        $permissions = $this->_identityService->GetAvailablePermissions();
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