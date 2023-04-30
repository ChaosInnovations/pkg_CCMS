<?php

namespace Pivel\Hydro2\Controllers;

use Pivel\Hydro2\Models\HTTP\Method;
use Pivel\Hydro2\Models\HTTP\StatusCode;
use Pivel\Hydro2\Extensions\Query;
use Pivel\Hydro2\Extensions\Route;
use Pivel\Hydro2\Extensions\RoutePrefix;
use Pivel\Hydro2\Models\Database\DatabaseConfigurationProfile;
use Pivel\Hydro2\Models\Database\Order;
use Pivel\Hydro2\Models\EntityPersistenceProfile;
use Pivel\Hydro2\Models\HTTP\JsonResponse;
use Pivel\Hydro2\Models\HTTP\Request;
use Pivel\Hydro2\Models\HTTP\Response;
use Pivel\Hydro2\Models\Identity\Permission;
use Pivel\Hydro2\Models\Permissions;
use Pivel\Hydro2\Services\Database\DatabaseService;
use Pivel\Hydro2\Services\Entity\IEntityService;
use Pivel\Hydro2\Services\IdentityService;
use Pivel\Hydro2\Services\ILoggerService;

#[RoutePrefix('api/hydro2/persistence')]
class PersistenceProfilesController extends BaseController
{
    private ILoggerService $_logger;
    private IEntityService $_entityService;
    private IdentityService $_identityService;

    public function __construct(
        ILoggerService $logger,
        IEntityService $entityService,
        IdentityService $identityService,
        Request $request,
    ) {
        $this->_logger = $logger;
        $this->_entityService = $entityService;
        $this->_identityService = $identityService;
        parent::__construct($request);
    }

    #[Route(Method::GET, 'profiles')]
    public function GetAllProfiles(): Response
    {
        $requestUser = $this->_identityService->GetUserFromRequestOrVisitor($this->request);
        if (!$requestUser->GetUserRole()->HasPermission(Permissions::ManagePersistenceProfiles->value)) {
            return new Response(
                status: StatusCode::NotFound,
            );
        }

        $query = new Query();
        $query->Limit($this->request->Args['limit'] ?? -1);
        $query->Offset($this->request->Args['offset'] ?? 0);

        if (isset($this->request->Args['sort_by'])) {
            $dir = Order::tryFrom(strtoupper($this->request->Args['sort_dir']??'asc'))??Order::Ascending;
            $query->OrderBy($this->request->Args['sort_by']??'key', $dir);
        }

        $r = $this->_entityService->GetRepository(EntityPersistenceProfile::class);

        /** @var EntityPersistenceProfile[] */
        $profiles = $r->Read($query);

        $serializedProfiles = [];
        foreach ($profiles as $profile) {
            $serializedProfiles[] = [
                'key' => $profile->GetKey(),
                'persistenceProviderClass' => $profile->GetPersistenceProviderFriendlyName(),
                'hostOrPath' => $profile->GetHostOrPath(),
                'username' => $profile->GetUsername(),
                // Don't return the password.
                'databaseSchema' => $profile->GetDatabaseSchema(),
            ];
        }

        return new JsonResponse(
            data: [
                'persistenceprofiles' => $serializedProfiles,
            ],
            status: StatusCode::OK,
        );
    }

    #[Route(Method::GET, 'providers')]
    public function GetProviders(): Response
    {
        $requestUser = $this->_identityService->GetUserFromRequestOrVisitor($this->request);
        if (!$requestUser->GetUserRole()->HasPermission(Permissions::ManagePersistenceProfiles->value)) {
            return new Response(
                status: StatusCode::NotFound,
            );
        }

        return new JsonResponse(
            data: [
                'persistenceproviders' => $this->_entityService->GetAvailableProviders(),
            ],
            status: StatusCode::OK,
        );
    }

    #[Route(Method::POST, 'validatehost')]
    public function ValidateHost(): Response
    {
        $requestUser = $this->_identityService->GetUserFromRequestOrVisitor($this->request);
        if (!$requestUser->GetUserRole()->HasPermission(Permissions::ManagePersistenceProfiles->value)) {
            return new Response(
                status: StatusCode::NotFound,
            );
        }

        // validate args
        if (!isset($this->request->Args['provider'])) {
            // missing argument
            // return response with code 400 (Bad Request)
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'provider',
                            'description' => 'Database provider name',
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

        if (!$this->_entityService->IsProviderValid($this->request->Args['provider'])) {
            // invalid provider argument
            // return response with code 400 (Bad Request)
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'provider',
                            'description' => 'Database provider name',
                            'message' => 'Selected provider is not supported.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are invalid.'
            );
        }

        $profile = new EntityPersistenceProfile();
        $profile->SetProfile(
            persistenceProviderClass: $this->request->Args['provider'],
            hostOrPath: $this->request->Args['host'],
        );

        return new JsonResponse(
            data: [
                'persistencehostcheck' => $this->_entityService->IsHostValid($profile),
            ],
            status: StatusCode::OK,
        );
    }

    #[Route(Method::POST, 'validateuser')]
    public function ValidateUser(): Response
    {
        $requestUser = $this->_identityService->GetUserFromRequestOrVisitor($this->request);
        // check whether the session/user is allowed to view the admin panel at all.
        if (!$requestUser->GetUserRole()->HasPermission(Permissions::ManagePersistenceProfiles->value)) {
            return new Response(
                status: StatusCode::NotFound,
            );
        }

        // validate args
        if (!isset($this->request->Args['provider'])) {
            // missing argument
            // return response with code 400 (Bad Request)
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'provider',
                            'description' => 'Database provider name',
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

        if (!$this->_entityService->IsProviderValid($this->request->Args['provider'])) {
            // invalid provider argument
            // return response with code 400 (Bad Request)
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'provider',
                            'description' => 'Database provider name',
                            'message' => 'Selected provider is not supported.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are invalid.'
            );
        }

        $profile = new EntityPersistenceProfile();
        $profile->SetProfile(
            persistenceProviderClass: $this->request->Args['provider'],
            hostOrPath: $this->request->Args['host'],
            username: $username = $this->request->Args['username'] ?? null,
            password: $this->request->Args['password'] ?? null,
        );

        if (!$this->_entityService->IsHostValid($profile)) {
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

        return new JsonResponse(
            data: [
                'persistenceusercheck' => [
                    'valid' => $this->_entityService->IsUserValid($profile),
                    'cancreatedb' => $profile->GetPersistenceProvider()->CanCreateDatabaseSchemas(),
                ],
            ],
            status: StatusCode::OK,
        );
    }

    #[Route(Method::POST, 'getdatabases')]
    public function GetDatabases(): Response
    {
        $requestUser = $this->_identityService->GetUserFromRequestOrVisitor($this->request);
        // check whether the session/user is allowed to view the admin panel at all.
        if (!$requestUser->GetUserRole()->HasPermission(Permissions::ManagePersistenceProfiles->value)) {
            return new Response(
                status: StatusCode::NotFound,
            );
        }

        // validate args
        if (!isset($this->request->Args['provider'])) {
            // missing argument
            // return response with code 400 (Bad Request)
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'provider',
                            'description' => 'Database provider name',
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

        if (!$this->_entityService->IsProviderValid($this->request->Args['provider'])) {
            // invalid provider argument
            // return response with code 400 (Bad Request)
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'provider',
                            'description' => 'Database provider name',
                            'message' => 'Selected provider is not supported.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are invalid.'
            );
        }

        $profile = new EntityPersistenceProfile();
        $profile->SetProfile(
            persistenceProviderClass: $this->request->Args['provider'],
            hostOrPath: $this->request->Args['host'],
            username: $username = $this->request->Args['username'] ?? null,
            password: $this->request->Args['password'] ?? null,
        );

        if (!$this->_entityService->IsHostValid($profile)) {
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

        if (!$this->_entityService->IsUserValid($profile)) {
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

        // return list of databases
        return new JsonResponse(
            data: [
                'availabledatabaseschemas' => $profile->GetPersistenceProvider()->GetDatabaseSchemas(),
            ],
            status: StatusCode::OK,
        );
    }

    #[Route(Method::POST, 'profiles/{key}')]
    #[Route(Method::POST, 'profiles')]
    public function SaveConfiguration(): Response
    {
        $requestUser = $this->_identityService->GetUserFromRequestOrVisitor($this->request);
        // check whether the session/user is allowed to view the admin panel at all.
        if (!$requestUser->GetUserRole()->HasPermission(Permissions::ManagePersistenceProfiles->value)) {
            return new Response(
                status: StatusCode::NotFound,
            );
        }

        // validate args
        if (!isset($this->request->Args['provider'])) {
            // missing argument
            // return response with code 400 (Bad Request)
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'provider',
                            'description' => 'Database provider name',
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

        if (!$this->_entityService->IsProviderValid($this->request->Args['provider'])) {
            // invalid provider argument
            // return response with code 400 (Bad Request)
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'provider',
                            'description' => 'Database provider name',
                            'message' => 'Selected provider is not supported.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are invalid.'
            );
        }

        $profile = new EntityPersistenceProfile($this->request->Args['key']??'primary');
        $profile->SetProfile(
            persistenceProviderClass: $this->request->Args['provider'],
            hostOrPath: $this->request->Args['host'],
            username: $username = $this->request->Args['username'] ?? null,
            password: $this->request->Args['password'] ?? null,
            databaseSchema: $this->request->Args['database'] ?? null,
        );

        if (!$this->_entityService->IsHostValid($profile)) {
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

        if (!$this->_entityService->IsUserValid($profile)) {
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

        $canCreateDatabaseSchemas = $profile->GetPersistenceProvider()->CanCreateDatabaseSchemas();
        $databaseSchemas = $profile->GetPersistenceProvider()->GetDatabaseSchemas();

        // check that selected database is valid, or create a new one if selected, unless there are none because then this is probably Sqlite
        if (count($databaseSchemas) != 0 && !$canCreateDatabaseSchemas && !in_array($profile->GetDatabaseSchema(), $databaseSchemas)) {
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

        if ($canCreateDatabaseSchemas && !in_array($profile->GetDatabaseSchema(), $databaseSchemas)) {
            if (!$profile->GetPersistenceProvider()->CreateDatabaseSchema($profile->GetDatabaseSchema())) {
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

            $this->_logger->Debug('Pivel/Hydro2', "Database schema {$profile->GetDatabaseSchema()} created.");
        }

        
        
        return new JsonResponse(
            data: [
                'result' => $this->_entityService->SavePersistenceProfile($profile),
            ],
            status: StatusCode::OK,
        );
    }
}