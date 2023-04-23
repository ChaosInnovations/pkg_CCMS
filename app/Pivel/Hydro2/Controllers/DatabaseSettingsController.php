<?php

namespace Pivel\Hydro2\Controllers;

use Pivel\Hydro2\Models\HTTP\Method;
use Pivel\Hydro2\Models\HTTP\StatusCode;
use Pivel\Hydro2\Extensions\Database\OrderBy;
use Pivel\Hydro2\Extensions\Route;
use Pivel\Hydro2\Extensions\RoutePrefix;
use Pivel\Hydro2\Models\Database\DatabaseConfigurationProfile;
use Pivel\Hydro2\Models\Database\Order;
use Pivel\Hydro2\Models\HTTP\JsonResponse;
use Pivel\Hydro2\Models\HTTP\Request;
use Pivel\Hydro2\Models\HTTP\Response;
use Pivel\Hydro2\Services\Database\DatabaseService;
use Pivel\Hydro2\Services\IdentityService;

#[RoutePrefix('api/hydro2/database')]
class DatabaseSettingsController extends BaseController
{
    protected IdentityService $_identityService;
    protected DatabaseService $_databaseService;

    public function __construct(
        IdentityService $identityService,
        DatabaseService $databaseService,
        Request $request,
    )
    {
        $this->_identityService = $identityService;
        $this->_databaseService = $databaseService;
        parent::__construct($request);
    }

    #[Route(Method::GET, 'profiles')]
    public function GetAllProfiles() : Response
    {
        // if database has already been configured and not logged in as admin, return 404
        if (!$this->_databaseService->IsPrimaryConnected() || !$this->_identityService->GetRequestUser($this->request)->Role->HasPermission("pivel/hydro2/managedatabaseconnections")) {
            return new Response(
                status: StatusCode::NotFound
            );
        }

        $order = null;
        if (isset($this->request->Args['sort_by'])) {
            $dir = Order::tryFrom(strtoupper($this->request->Args['sort_dir']??'asc'))??Order::Ascending;
            $order = (new OrderBy)->Column($this->request->Args['sort_by']??'key', $dir);
        }
        $limit = $this->request->Args['limit']??null;
        $offset = $this->request->Args['offset']??null;

        $profiles = DatabaseConfigurationProfile::GetAll($order, $limit, $offset);
        $serializedProfiles = [];
        foreach ($profiles as $profile) {
            $serializedProfiles[] = [
                'key' => $profile->Key,
                'driver' => $profile->Driver,
                'host' => $profile->Host,
                'username' => $profile->Username,
                // Don't return the password.
                'database' => $profile->DatabaseSchema,
            ];
        }

        return new JsonResponse(
            data: [
                'databaseprofiles' => $serializedProfiles,
            ],
            status: StatusCode::OK,
        );
    }

    #[Route(Method::GET, 'drivers')]
    public function GetDrivers() : Response
    {
        // if database has already been configured and not logged in as admin, return 404
        if (!$this->_databaseService->IsPrimaryConnected() || !$this->_identityService->GetRequestUser($this->request)->Role->HasPermission("pivel/hydro2/managedatabaseconnections")) {
            return new Response(
                status: StatusCode::NotFound
            );
        }

        return new JsonResponse(
            data: [
                'databasedrivers' => $this->_databaseService->GetAvailableDrivers(),
            ],
            status: StatusCode::OK,
        );
    }

    #[Route(Method::POST, 'validatehost')]
    public function ValidateHost() : Response {
        // if database has already been configured and not logged in as admin, return 404
        if (!$this->_databaseService->IsPrimaryConnected() || !$this->_identityService->GetRequestUser($this->request)->Role->HasPermission("pivel/hydro2/managedatabaseconnections")) {
            return new Response(
                status: StatusCode::NotFound
            );
        }

        // validate args
        if (!isset($this->request->Args['driver'])) {
            // missing argument
            // return response with code 400 (Bad Request)
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'driver',
                            'description' => 'Database driver name',
                            'message' => 'Argument is missing.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are missing.'
            );
        }

        if (!isset($this->request->Args['host'])) {
            // missing argument
            // return response with code 400 (Bad Request)
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'host',
                            'description' => 'Database host',
                            'message' => 'Argument is missing.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: "One or more arguments are missing."
            );
        }

        if (!$this->_databaseService->CheckDriver($this->request->Args['driver'])) {
            // invalid driver argument
            // return response with code 400 (Bad Request)
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'driver',
                            'description' => 'Database driver name',
                            'message' => 'Selected driver is not supported.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are invalid.'
            );
        }

        // check if host is a database server that matches selected driver
        if (!$this->_databaseService->CheckHost($this->request->Args['driver'], $this->request->Args['host'])) {
            return new JsonResponse(
                data: [
                    'databasehostcheck' => false,
                ],
                status: StatusCode::OK,
            );
        }

        // driver and host are both valid.

        return new JsonResponse(
            data: [
                'databasehostcheck' => true,
            ],
            status: StatusCode::OK,
        );
    }

    #[Route(Method::POST, 'validateuser')]
    public function ValidateUser() : Response {
        // if database has already been configured and not logged in as admin, return 404
        if (!$this->_databaseService->IsPrimaryConnected() || !$this->_identityService->GetRequestUser($this->request)->Role->HasPermission("pivel/hydro2/managedatabaseconnections")) {
            return new Response(
                status: StatusCode::NotFound
            );
        }

        // validate args
        if (!isset($this->request->Args['driver'])) {
            // missing argument
            // return response with code 400 (Bad Request)
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'driver',
                            'description' => 'Database driver name',
                            'message' => 'Argument is missing.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are missing.'
            );
        }

        if (!isset($this->request->Args['host'])) {
            // missing argument
            // return response with code 400 (Bad Request)
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'host',
                            'description' => 'Database host',
                            'message' => 'Argument is missing.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: "One or more arguments are missing."
            );
        }

        if (!$this->_databaseService->CheckDriver($this->request->Args['driver'])) {
            // invalid driver argument
            // return response with code 400 (Bad Request)
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'driver',
                            'description' => 'Database driver name',
                            'message' => 'Selected driver is not supported.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are invalid.'
            );
        }

        if (!$this->_databaseService->CheckHost($this->request->Args['driver'], $this->request->Args['host'])) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'host',
                            'description' => 'Database host',
                            'message' => 'Host not accessible.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are invalid.'
            );
        }

        $username = $this->request->Args['username'] ?? null;
        $password = $this->request->Args['password'] ?? null;

        // check if this is a valid user/password on the selected host
        $privileges = $this->_databaseService->GetPrivileges(
            $this->request->Args['driver'],
            $this->request->Args['host'],
            $username,
            $password,
        );

        if ($privileges === false) {
            return new JsonResponse(
                data: [
                    'databaseusercheck' => [
                        'valid' => false,
                    ],
                ],
                status: StatusCode::OK,
            );
        }

        // return list of user's privileges
        return new JsonResponse(
            data: [
                'databaseusercheck' => $privileges,
            ],
            status: StatusCode::OK,
        );
    }

    #[Route(Method::POST, 'getdatabases')]
    public function GetDatabases() : Response {
        // if database has already been configured and not logged in as admin, return 404
        if (!$this->_databaseService->IsPrimaryConnected() || !$this->_identityService->GetRequestUser($this->request)->Role->HasPermission("pivel/hydro2/managedatabaseconnections")) {
            return new Response(
                status: StatusCode::NotFound
            );
        }

        // validate args
        if (!isset($this->request->Args['driver'])) {
            // missing argument
            // return response with code 400 (Bad Request)
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'driver',
                            'description' => 'Database driver name',
                            'message' => 'Argument is missing.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are missing.'
            );
        }

        if (!isset($this->request->Args['host'])) {
            // missing argument
            // return response with code 400 (Bad Request)
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'host',
                            'description' => 'Database host',
                            'message' => 'Argument is missing.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: "One or more arguments are missing."
            );
        }

        if (!$this->_databaseService->CheckDriver($this->request->Args['driver'])) {
            // invalid driver argument
            // return response with code 400 (Bad Request)
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'driver',
                            'description' => 'Database driver name',
                            'message' => 'Selected driver is not supported.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are invalid.'
            );
        }

        if (!$this->_databaseService->CheckHost($this->request->Args['driver'], $this->request->Args['host'])) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'host',
                            'description' => 'Database host',
                            'message' => 'Host not accessible.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are invalid.'
            );
        }

        $username = $this->request->Args['username'] ?? null;
        $password = $this->request->Args['password'] ?? null;

        // check if this is a valid user/password on the selected host
        $privileges = $this->_databaseService->GetPrivileges(
            $this->request->Args['driver'],
            $this->request->Args['host'],
            $username,
            $password,
        );

        if ($privileges === false) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'username',
                            'description' => 'Database username.',
                            'message' => 'The username or password is incorrect.',
                        ],
                        [
                            'name' => 'password',
                            'description' => 'Database user password.',
                            'message' => 'The username or password is incorrect.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are invalid.'
            );
        }

        // get a list of databases which this user has privileges on
        // can't be false because that would mean the user is invalid, which we've already checked.
        $databases = $this->_databaseService->GetDatabases(
            $this->request->Args['driver'],
            $this->request->Args['host'],
            $username,
            $password,
        );

        // return list of databases
        return new JsonResponse(
            data: [
                'availabledatabases' => $databases,
            ],
            status: StatusCode::OK,
        );
    }

    #[Route(Method::POST, 'profiles/{key}')]
    #[Route(Method::POST, 'profiles')]
    public function SaveConfiguration() : Response {
        // if database has already been configured and not logged in as admin, return 404
        if (!$this->_databaseService->IsPrimaryConnected() || !$this->_identityService->GetRequestUser($this->request)->Role->HasPermission("pivel/hydro2/managedatabaseconnections")) {
            return new Response(
                status: StatusCode::NotFound
            );
        }

        // validate args
        if (!isset($this->request->Args['driver'])) {
            // missing argument
            // return response with code 400 (Bad Request)
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'driver',
                            'description' => 'Database driver name',
                            'message' => 'Argument is missing.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are missing.'
            );
        }

        if (!isset($this->request->Args['host'])) {
            // missing argument
            // return response with code 400 (Bad Request)
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'host',
                            'description' => 'Database host',
                            'message' => 'Argument is missing.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: "One or more arguments are missing."
            );
        }

        if (!$this->_databaseService->CheckDriver($this->request->Args['driver'])) {
            // invalid driver argument
            // return response with code 400 (Bad Request)
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'driver',
                            'description' => 'Database driver name',
                            'message' => 'Selected driver is not supported.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are invalid.'
            );
        }

        if (!$this->_databaseService->CheckHost($this->request->Args['driver'], $this->request->Args['host'])) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'host',
                            'description' => 'Database host',
                            'message' => 'Host not accessible.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are invalid.'
            );
        }

        $username = $this->request->Args['username'] ?? null;
        $password = $this->request->Args['password'] ?? null;

        // check if this is a valid user/password on the selected host
        $privileges = $this->_databaseService->GetPrivileges(
            $this->request->Args['driver'],
            $this->request->Args['host'],
            $username,
            $password,
        );

        if ($privileges === false) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'username',
                            'description' => 'Database username.',
                            'message' => 'The username or password is incorrect.',
                        ],
                        [
                            'name' => 'password',
                            'description' => 'Database user password.',
                            'message' => 'The username or password is incorrect.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are invalid.'
            );
        }

        $database = $this->request->Args['database'] ?? null;

        // get a list of databases which this user has privileges on
        // can't be false because that would mean the user is invalid, which we've already checked.
        $databases = $this->_databaseService->GetDatabases(
            $this->request->Args['driver'],
            $this->request->Args['host'],
            $username,
            $password,
        );

        // check that selected database is valid, or create a new one if selected
        if (!$privileges['cancreatedb'] && !in_array($database, $databases)) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'database',
                            'description' => 'Database to select or create.',
                            'message' => 'The database doesn\'t exist, or the user doesn\'t have privileges on it.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are invalid.'
            );
        }

        if ($privileges['cancreatedb'] && !in_array($database, $databases)) {
            if (!$this->_databaseService->CreateDatabase(
                $this->request->Args['driver'],
                $this->request->Args['host'],
                $username,
                $password,
                $database,
            )) {
                return new JsonResponse(
                    data: [
                        'validation_errors' => [
                            [
                                'name' => 'database',
                                'description' => 'Database to select or create.',
                                'message' => 'The database doesn\'t exist, but failed to create new database.',
                            ],
                        ],
                    ],
                    status: StatusCode::BadRequest,
                    error_message: 'One or more arguments are invalid.'
                ); 
            }
        }

        $configurationKey = $this->request->Args['key']??'primary';

        $profile = DatabaseConfigurationProfile::LoadFromKey($configurationKey)??new DatabaseConfigurationProfile($configurationKey,'','');

        $profile->Driver = $this->request->Args['driver'];
        $profile->Host = $this->request->Args['host'];
        $profile->Username = $username;
        $profile->Password = $password;
        $profile->DatabaseSchema = $database;

        // save configuration settings
        $profile->Save();
        
        return new JsonResponse(
            data: [
                'result' => true,
            ],
            status: StatusCode::OK,
        );
    }
}