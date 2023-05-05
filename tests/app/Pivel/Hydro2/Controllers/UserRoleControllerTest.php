<?php

namespace Tests;

use Mocks\Services\MockIdentityService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Pivel\Hydro2\Controllers\BaseController;
use Pivel\Hydro2\Controllers\UserRoleController;
use Pivel\Hydro2\Extensions\Query;
use Pivel\Hydro2\Models\Database\Order;
use Pivel\Hydro2\Models\HTTP\Request;
use Pivel\Hydro2\Models\HTTP\StatusCode;
use Pivel\Hydro2\Models\Identity\Permission;
use Pivel\Hydro2\Models\Identity\User;
use Pivel\Hydro2\Models\Identity\UserRole;
use Pivel\Hydro2\Models\Permissions;
use ReflectionClass;

#[CoversClass(UserRoleController::class)]
#[CoversClass(BaseController::class)]
#[UsesClass(UserRoleController::class)]
class UserRoleControllerTest extends TestCase
{
    public function testConstruction()
    {
        $mockIdentityService = new MockIdentityService();
        $mockRequest = new Request([]);

        $result = new UserRoleController(
            $mockIdentityService,
            $mockRequest,
        );

        $this->assertInstanceOf(UserRoleController::class, $result);
    }

    // ==== GetUserRoles ====

    public function testGetUserRolesShouldReturnNotFoundWithoutAuthentication()
    {
        $mockIdentityService = new MockIdentityService();
        $mockRequest = new Request([]);

        $result = new UserRoleController(
            $mockIdentityService,
            $mockRequest,
        );

        $response = $result->GetUserRoles();

        $this->assertEquals(StatusCode::NotFound, $response->getStatus());
    }

    public function testGetUserRolesShouldReturnArray()
    {
        $mockIdentityService = new MockIdentityService();
        $mockRequest = new Request([]);

        $role = new UserRole();
        $role->GrantPermission(Permissions::ManageUserRoles->value);

        $mockIdentityService->user = new User(
            role: $role,
        );
        $mockIdentityService->allUserRoles = [
            new UserRole('Fake Role 1'),
            new UserRole('Fake Role 2'),
        ];

        $result = new UserRoleController(
            $mockIdentityService,
            $mockRequest,
        );

        $response = $result->GetUserRoles();

        $this->assertEquals(StatusCode::OK, $response->getStatus());
        $responseValue = json_decode($response->getContent(), true);
        $this->assertIsArray($responseValue['data']['user_roles']);
    }

    public function testGetUserRolesShouldSetSortDirectionCorrectly()
    {
        $mockIdentityService = new MockIdentityService();
        $mockRequest = new Request([], post: ['sort_by' => 'id', 'sort_dir' => 'desc']);

        $role = new UserRole();
        $role->GrantPermission(Permissions::ManageUserRoles->value);

        $mockIdentityService->user = new User(
            role: $role,
        );
        $mockIdentityService->allUserRoles = [
            $role,
            new UserRole('Fake Role 2'),
        ];

        $result = new UserRoleController(
            $mockIdentityService,
            $mockRequest,
        );

        $response = $result->GetUserRoles();

        $this->assertEquals(StatusCode::OK, $response->getStatus());
        $responseValue = json_decode($response->getContent(), true);
        $this->assertIsArray($responseValue['data']['user_roles']);
        $this->assertInstanceOf(Query::class, $mockIdentityService->lastRequestedQuery);
        $this->assertEquals(Order::Descending, $mockIdentityService->lastRequestedQuery->GetOrderTree()[0]['direction']);
    }

    // ==== CreateUserRole ====

    public function testCreateUserRoleShouldReturnNotFoundWithoutAuthentication()
    {
        $mockIdentityService = new MockIdentityService();
        $mockRequest = new Request([]);

        $result = new UserRoleController(
            $mockIdentityService,
            $mockRequest,
        );

        $response = $result->CreateUserRole();

        $this->assertEquals(StatusCode::NotFound, $response->getStatus());
    }

    public function testCreateUserRoleShouldReturnBadRequestIfInvalidPermission()
    {
        $mockIdentityService = new MockIdentityService();
        $mockRequest = new Request([], post: ['permissions' => ['fakeValue']]);

        $role = new UserRole();
        $role->GrantPermission(Permissions::CreateUserRoles->value);

        $mockIdentityService->user = new User(
            role: $role,
        );
        $mockIdentityService->userRole = null;

        $result = new UserRoleController(
            $mockIdentityService,
            $mockRequest,
        );

        $response = $result->CreateUserRole();

        $this->assertEquals(StatusCode::BadRequest, $response->getStatus());
        $responseValue = json_decode($response->getContent(), true);
        $this->assertEquals("The permission 'fakeValue' doesn't exist.", $responseValue['data']['validation_errors'][0]['message']);
    }

    public function testCreateUserRoleShouldReturnServerErrorIfCantCreate()
    {
        $mockIdentityService = new MockIdentityService();
        $mockRequest = new Request([], post: ['permissions' => ['vendor/package/fakeValue']]);

        $role = new UserRole();
        $role->GrantPermission(Permissions::CreateUserRoles->value);

        $mockIdentityService->user = new User(
            role: $role,
        );
        $mockIdentityService->userRole = null;
        $mockIdentityService->availPermissions = ['vendor/package/fakeValue' => new Permission('vendor','package','fakeValue','','')];

        $result = new UserRoleController(
            $mockIdentityService,
            $mockRequest,
        );

        $response = $result->CreateUserRole();

        $this->assertEquals(StatusCode::InternalServerError, $response->getStatus());
        $responseValue = json_decode($response->getContent(), true);
        $this->assertEquals("There was a problem with the database.", $responseValue['message']);
    }

    public function testCreateUserRoleShouldReturnServerErrorIfCantGrantPermissions()
    {
        $mockIdentityService = new MockIdentityService();
        $mockRequest = new Request([], post: ['permissions' => ['vendor/package/fakeValue']]);

        $role = new UserRole();
        $role->GrantPermission(Permissions::CreateUserRoles->value);

        $mockIdentityService->user = new User(
            role: $role,
        );
        $mockIdentityService->userRole = null;
        $mockIdentityService->availPermissions = ['vendor/package/fakeValue' => new Permission('vendor','package','fakeValue','','')];
        $mockIdentityService->beSuccessful = true;

        $result = new UserRoleController(
            $mockIdentityService,
            $mockRequest,
        );

        $response = $result->CreateUserRole();

        $this->assertEquals(StatusCode::InternalServerError, $response->getStatus());
        $responseValue = json_decode($response->getContent(), true);
        $this->assertEquals("The UserRole was generated, but there was a problem with the database while adding permissions.", $responseValue['message']);
    }

    public function testCreateUserRoleShouldReturnArray()
    {
        $mockIdentityService = new MockIdentityService();
        $mockRequest = new Request([], post: ['permissions' => ['vendor/package/fakeValue']]);

        $role = new UserRole();
        $role->GrantPermission(Permissions::CreateUserRoles->value);

        $mockIdentityService->user = new User(
            role: $role,
        );
        $mockIdentityService->userRole = null;
        $mockIdentityService->availPermissions = ['vendor/package/fakeValue' => new Permission('vendor','package','fakeValue','','')];
        $mockIdentityService->beSuccessful = true;
        $mockIdentityService->fakePermissionToGrantNewRole = 'vendor/package/fakeValue';

        $result = new UserRoleController(
            $mockIdentityService,
            $mockRequest,
        );

        $response = $result->CreateUserRole();

        $this->assertEquals(StatusCode::OK, $response->getStatus());
        $responseValue = json_decode($response->getContent(), true);
        $this->assertIsArray($responseValue['data']['user_roles']);
    }

    // ==== GetUserRole ====

    public function testGetUserRoleShouldReturnNotFoundWithoutAuthentication()
    {
        $mockIdentityService = new MockIdentityService();
        $mockRequest = new Request([]);

        $result = new UserRoleController(
            $mockIdentityService,
            $mockRequest,
        );

        $response = $result->GetUserRole();

        $this->assertEquals(StatusCode::NotFound, $response->getStatus());
    }
    
    public function testGetUserRoleShouldReturnBadRequestIfInvalidId()
    {
        $mockIdentityService = new MockIdentityService();
        $mockRequest = new Request([], post: ['id' => 1]);

        $role = new UserRole();
        $role->GrantPermission(Permissions::ManageUserRoles->value);

        $mockIdentityService->user = new User(
            role: $role,
        );
        $mockIdentityService->userRole = null;

        $result = new UserRoleController(
            $mockIdentityService,
            $mockRequest,
        );

        $response = $result->GetUserRole();

        $this->assertEquals(StatusCode::BadRequest, $response->getStatus());
        $responseValue = json_decode($response->getContent(), true);
        $this->assertEquals("This user role doesn't exist.", $responseValue['data']['validation_errors'][0]['message']);
    }
    
    public function testGetUserRoleShouldReturnArray()
    {
        $mockIdentityService = new MockIdentityService();
        $mockRequest = new Request([], post: ['id' => 1]);

        $role = new UserRole();
        $role->GrantPermission(Permissions::ManageUserRoles->value);

        $mockIdentityService->user = new User(
            role: $role,
        );
        $mockIdentityService->userRole = $role;

        $result = new UserRoleController(
            $mockIdentityService,
            $mockRequest,
        );

        $response = $result->GetUserRole();

        $this->assertEquals(StatusCode::OK, $response->getStatus());
        $responseValue = json_decode($response->getContent(), true);
        $this->assertIsArray($responseValue['data']['user_roles']);
    }

    // ==== UpdateUserRole ====

    public function testUpdateUserRoleShouldReturnNotFoundWithoutAuthentication()
    {
        $mockIdentityService = new MockIdentityService();
        $mockRequest = new Request([]);

        $result = new UserRoleController(
            $mockIdentityService,
            $mockRequest,
        );

        $response = $result->UpdateUserRole();

        $this->assertEquals(StatusCode::NotFound, $response->getStatus());
    }

    public function testUpdateUserRoleShouldReturnBadRequestIfInvalidRoleId()
    {
        $mockIdentityService = new MockIdentityService();
        $mockRequest = new Request([], post: ['id' => 1]);

        $role = new UserRole();
        $role->GrantPermission(Permissions::ManageUserRoles->value);

        $mockIdentityService->user = new User(
            role: $role,
        );
        $mockIdentityService->userRole = null;

        $result = new UserRoleController(
            $mockIdentityService,
            $mockRequest,
        );

        $response = $result->UpdateUserRole();

        $this->assertEquals(StatusCode::BadRequest, $response->getStatus());
        $responseValue = json_decode($response->getContent(), true);
        $this->assertEquals("This user role doesn't exist.", $responseValue['data']['validation_errors'][0]['message']);
    }

    public function testUpdateUserRoleShouldReturnBadRequestIfInvalidPermission()
    {
        $mockIdentityService = new MockIdentityService();
        $mockRequest = new Request([], post: ['id' => 1, 'permissions' => ['fakeValue']]);

        $role = new UserRole();
        $role->GrantPermission(Permissions::ManageUserRoles->value);

        $mockIdentityService->user = new User(
            role: $role,
        );
        $mockIdentityService->userRole = new UserRole();

        $result = new UserRoleController(
            $mockIdentityService,
            $mockRequest,
        );

        $response = $result->UpdateUserRole();

        $this->assertEquals(StatusCode::BadRequest, $response->getStatus());
        $responseValue = json_decode($response->getContent(), true);
        $this->assertEquals("The permission 'fakeValue' doesn't exist.", $responseValue['data']['validation_errors'][0]['message']);
    }

    public function testUpdateUserRoleShouldReturnServerErrorIfCantSave()
    {
        $mockIdentityService = new MockIdentityService();
        $mockRequest = new Request([], post: ['id' => 1]);

        $role = new UserRole();
        $role->GrantPermission(Permissions::ManageUserRoles->value);

        $mockIdentityService->user = new User(
            role: $role,
        );
        $mockIdentityService->userRole = new UserRole();

        $result = new UserRoleController(
            $mockIdentityService,
            $mockRequest,
        );

        $response = $result->UpdateUserRole();

        $this->assertEquals(StatusCode::InternalServerError, $response->getStatus());
        $responseValue = json_decode($response->getContent(), true);
        $this->assertEquals("There was a problem with the database.", $responseValue['message']);
    }

    public function testUpdateUserRoleShouldReturnServerErrorIfCantGrantPermissions()
    {
        $mockIdentityService = new MockIdentityService();
        $mockRequest = new Request([], post: ['id' => 1, 'permissions' => ['vendor/package/fakeValue']]);

        $role = new UserRole();
        $role->GrantPermission(Permissions::ManageUserRoles->value);

        $mockIdentityService->user = new User(
            role: $role,
        );
        $mockIdentityService->userRole = new UserRole();
        //$mockIdentityService->userRole->GrantPermission('vendor/package/fakeValue2');
        $mockIdentityService->availPermissions = ['vendor/package/fakeValue' => new Permission('vendor','package','fakeValue','','')];
        $mockIdentityService->beSuccessful = true;

        $result = new UserRoleController(
            $mockIdentityService,
            $mockRequest,
        );

        $response = $result->UpdateUserRole();

        $this->assertEquals(StatusCode::InternalServerError, $response->getStatus());
        $responseValue = json_decode($response->getContent(), true);
        $this->assertEquals("The UserRole was updated, but there was a problem with the database while adding permissions.", $responseValue['message']);
    }

    public function testUpdateUserRoleShouldReturnServerErrorIfCantDenyPermissions()
    {
        $mockIdentityService = new MockIdentityService();
        $mockRequest = new Request([], post: ['id' => 1, 'permissions' => []]);

        $role = new UserRole();
        $role->GrantPermission(Permissions::ManageUserRoles->value);

        $mockIdentityService->user = new User(
            role: $role,
        );
        $mockIdentityService->userRole = new UserRole();
        $mockIdentityService->userRole->GrantPermission('vendor/package/fakeValue');
        $mockIdentityService->beSuccessful = true;

        $result = new UserRoleController(
            $mockIdentityService,
            $mockRequest,
        );

        $response = $result->UpdateUserRole();

        $this->assertEquals(StatusCode::InternalServerError, $response->getStatus());
        $responseValue = json_decode($response->getContent(), true);
        $this->assertEquals("The UserRole was updated, but there was a problem with the database while removing permissions.", $responseValue['message']);
    }

    public function testUpdateUserRoleShouldReturnOk()
    {
        $mockIdentityService = new MockIdentityService();
        $mockRequest = new Request([], post: ['id' => 1, 'permissions' => ['vendor/package/fakeValue']]);

        $role = new UserRole();
        $role->GrantPermission(Permissions::ManageUserRoles->value);

        $mockIdentityService->user = new User(
            role: $role,
        );
        $mockIdentityService->availPermissions = ['vendor/package/fakeValue' => new Permission('vendor','package','fakeValue','','')];
        $mockIdentityService->userRole = new UserRole();
        $mockIdentityService->userRole->GrantPermission('vendor/package/fakeValue');
        $mockIdentityService->beSuccessful = true;

        $result = new UserRoleController(
            $mockIdentityService,
            $mockRequest,
        );

        $response = $result->UpdateUserRole();

        $this->assertEquals(StatusCode::OK, $response->getStatus());
    }

    // ==== DeleteUserRole ====

    public function testDeleteUserRoleShouldReturnNotFoundWithoutAuthentication()
    {
        $mockIdentityService = new MockIdentityService();
        $mockRequest = new Request([]);

        $result = new UserRoleController(
            $mockIdentityService,
            $mockRequest,
        );

        $response = $result->DeleteUserRole();

        $this->assertEquals(StatusCode::NotFound, $response->getStatus());
    }

    public function testDeleteUserRoleShouldReturnBadRequestIfInvalidRoleId()
    {
        $mockIdentityService = new MockIdentityService();
        $mockRequest = new Request([], post: ['id' => 1]);

        $role = new UserRole();
        $role->GrantPermission(Permissions::ManageUserRoles->value);

        $mockIdentityService->user = new User(
            role: $role,
        );
        $mockIdentityService->userRole = null;

        $result = new UserRoleController(
            $mockIdentityService,
            $mockRequest,
        );

        $response = $result->DeleteUserRole();

        $this->assertEquals(StatusCode::BadRequest, $response->getStatus());
        $responseValue = json_decode($response->getContent(), true);
        $this->assertEquals("This user role doesn't exist.", $responseValue['data']['validation_errors'][0]['message']);
    }

    public function testDeleteUserRoleShouldReturnBadRequestIfUsersExist()
    {
        $mockIdentityService = new MockIdentityService();
        $mockRequest = new Request([], post: ['id' => 1]);

        $role = new UserRole();
        $role->GrantPermission(Permissions::ManageUserRoles->value);

        $mockIdentityService->user = new User(
            role: $role,
        );
        $mockIdentityService->userRole = new UserRole();
        $rc = new ReflectionClass(UserRole::class);
        $p = $rc->getProperty('tempUsers');
        $p->setValue($mockIdentityService->userRole, [$mockIdentityService->user]);

        $result = new UserRoleController(
            $mockIdentityService,
            $mockRequest,
        );

        $response = $result->DeleteUserRole();

        $this->assertEquals(StatusCode::BadRequest, $response->getStatus());
        $responseValue = json_decode($response->getContent(), true);
        $this->assertEquals("Cannot delete a role while there are users with this role.", $responseValue['data']['validation_errors'][0]['message']);
    }

    public function testDeleteUserRoleShouldReturnServerErrorIfCantDelete()
    {
        $mockIdentityService = new MockIdentityService();
        $mockRequest = new Request([], post: ['id' => 1]);

        $role = new UserRole();
        $role->GrantPermission(Permissions::ManageUserRoles->value);

        $mockIdentityService->user = new User(
            role: $role,
        );
        $mockIdentityService->userRole = new UserRole();

        $result = new UserRoleController(
            $mockIdentityService,
            $mockRequest,
        );

        $response = $result->DeleteUserRole();

        $this->assertEquals(StatusCode::InternalServerError, $response->getStatus());
        $responseValue = json_decode($response->getContent(), true);
        $this->assertEquals("There was a problem with the database.", $responseValue['message']);
    }

    public function testDeleteUserRoleShouldReturnStatusOk()
    {
        $mockIdentityService = new MockIdentityService();
        $mockRequest = new Request([], post: ['id' => 1]);

        $role = new UserRole();
        $role->GrantPermission(Permissions::ManageUserRoles->value);

        $mockIdentityService->user = new User(
            role: $role,
        );
        $mockIdentityService->userRole = new UserRole();
        $mockIdentityService->beSuccessful = true;

        $result = new UserRoleController(
            $mockIdentityService,
            $mockRequest,
        );

        $response = $result->DeleteUserRole();

        $this->assertEquals(StatusCode::OK, $response->getStatus());
    }

    // ==== GetPermissions ====

    public function testGetPermissionsShouldReturnNotFoundWithoutAuthentication()
    {
        $mockIdentityService = new MockIdentityService();
        $mockRequest = new Request([]);

        $result = new UserRoleController(
            $mockIdentityService,
            $mockRequest,
        );

        $response = $result->GetPermissions();

        $this->assertEquals(StatusCode::NotFound, $response->getStatus());
    }

    public function testGetPermissionsShouldReturnArray()
    {
        $mockIdentityService = new MockIdentityService();
        $mockRequest = new Request([]);

        $role = new UserRole();
        $role->GrantPermission(Permissions::ManageUserRoles->value);

        $mockIdentityService->user = new User(
            role: $role,
        );

        $mockIdentityService->availPermissions = [
            new Permission('vendor', 'package', 'key', 'name', 'description', []),
        ];

        $result = new UserRoleController(
            $mockIdentityService,
            $mockRequest,
        );

        $response = $result->GetPermissions();

        $this->assertEquals(StatusCode::OK, $response->getStatus());
        $responseValue = json_decode($response->getContent(), true);
        $this->assertIsArray($responseValue['data']['permissions']);
        $this->assertCount(1, $responseValue['data']['permissions']);
    }
}