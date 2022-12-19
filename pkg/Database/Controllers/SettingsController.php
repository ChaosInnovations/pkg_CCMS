<?php

namespace Package\Database\Controllers;

use Package\CCMS\Controllers\BaseController;
use Package\CCMS\Extensions\Route;
use Package\CCMS\Extensions\RoutePrefix;
use Package\CCMS\Models\HTTP\Method;
use Package\CCMS\Models\HTTP\StatusCode;
use Package\CCMS\Models\JsonResponse;
use Package\CCMS\Models\Response;
use Package\Database\Services\DatabaseService;

#[RoutePrefix('api/database/settings')]
class SettingsController extends BaseController
{
    private function UserHasPermission(string $permission) : bool {
        return false;
    }

    #[Route(Method::POST, 'getdrivers')]
    public function GetDrivers() : Response
    {
        // if database has already been configured and not logged in as admin, return 404
        if (!DatabaseService::IsPrimaryConnected() || !$this->UserHasPermission("database:admin")) {
            return new Response(
                status: StatusCode::NotFound
            );
        }

        // return response with code 400 (Bad Request)
        return new JsonResponse(
            data: [
                'databasedrivers' => DatabaseService::GetAvailableDrivers(),
            ],
            status: StatusCode::OK,
        );
    }

    #[Route(Method::POST, 'validatehost')]
    public function ValidateHost() : Response {
        // if database has already been configured and not logged in as admin, return 404
        if (!DatabaseService::IsPrimaryConnected() || !$this->UserHasPermission("database:admin")) {
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

        if (!DatabaseService::CheckDriver($this->request->Args['driver'])) {
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
        if (!DatabaseService::CheckHost($this->request->Args['driver'], $this->request->Args['host'])) {
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
        if (!DatabaseService::IsPrimaryConnected() || !$this->UserHasPermission("database:admin")) {
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

        if (!DatabaseService::CheckDriver($this->request->Args['driver'])) {
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

        if (!DatabaseService::CheckHost($this->request->Args['driver'], $this->request->Args['host'])) {
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
        $privileges = DatabaseService::GetPrivileges(
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
        if (!DatabaseService::IsPrimaryConnected() || !$this->UserHasPermission("database:admin")) {
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

        if (!DatabaseService::CheckDriver($this->request->Args['driver'])) {
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

        if (!DatabaseService::CheckHost($this->request->Args['driver'], $this->request->Args['host'])) {
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
        $privileges = DatabaseService::GetPrivileges(
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
        $databases = DatabaseService::GetDatabases(
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

    #[Route(Method::POST, 'configure')]
    public function SaveConfiguration() : Response {
        // if database has already been configured and not logged in as admin, return 404
        if (!DatabaseService::IsPrimaryConnected() || !$this->UserHasPermission("database:admin")) {
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

        if (!DatabaseService::CheckDriver($this->request->Args['driver'])) {
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

        if (!DatabaseService::CheckHost($this->request->Args['driver'], $this->request->Args['host'])) {
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
        $privileges = DatabaseService::GetPrivileges(
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
        $databases = DatabaseService::GetDatabases(
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
            if (!DatabaseService::CreateDatabase(
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

        // save configuration settings
        DatabaseService::UpdateConfiguration(
            $this->request->Args['driver'],
            $this->request->Args['host'],
            $username,
            $password,
            $database,
        );
        
        return new JsonResponse(
            data: [
                'result' => true,
            ],
            status: StatusCode::OK,
        );
    }
}