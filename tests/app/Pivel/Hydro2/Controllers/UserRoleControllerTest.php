<?php

namespace Tests;

use Mocks\Services\MockIdentityService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Pivel\Hydro2\Controllers\UserRoleController;
use Pivel\Hydro2\Extensions\Query;
use Pivel\Hydro2\Models\Database\Order;
use Pivel\Hydro2\Models\HTTP\Request;
use Pivel\Hydro2\Models\HTTP\StatusCode;
use Pivel\Hydro2\Models\Identity\User;
use Pivel\Hydro2\Models\Identity\UserRole;
use Pivel\Hydro2\Models\Permissions;

#[CoversClass(UserRoleController::class)]
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
}