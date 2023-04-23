<?php

namespace Pivel\Hydro2;

use Pivel\Hydro2\Models\HTTP\Request;
use Pivel\Hydro2\Models\HTTP\Response;
use Pivel\Hydro2\Services\AutoloadService;
use Pivel\Hydro2\Services\PackageManifestService;
use Pivel\Hydro2\Services\Router;
use Pivel\Hydro2\Services\RouterService;
use ReflectionClass;
use ReflectionNamedType;

class Hydro2
{
    /**
     * @param string $webDir
     * @param string $appDir
     * @param string[] $additionalAppDirs
     */
    public static function CreateHydro2App(string $webDir, string $appDir, array $additionalAppDirs) : Hydro2
    {
        // set up dependency injection container
            // if manifestcache.json is available, use that
            // otherwise scan for available packages
            // based on manifests, add:
                // Singleton classes, which are lazy-instantiated when that class is requested by another class and re-use the same instance
                // Transient classes, which are instantiated when requested then discarded
                // can either be added with class name directly, or registered with an interface name
        // set up routing service
            // if routes.json is available, use that

        // in Run:
        // find requested controller(s) based on routing service
        // instantiate each controller from DI container and execute it, merge with Response
        // once the Response's IsFinal flag is set, send the response to the client

        self::$Current = new Hydro2($webDir, $appDir, $additionalAppDirs);
        self::$Current->RegisterAutoloader();

        self::$Current->RegisterSingleton(PackageManifestService::class);
        self::$Current->RegisterSingleton(RouterService::class);

        self::$Current->ResolveManifestService(PackageManifestService::class);
        self::$Current->ResolveRouterService(RouterService::class);

        self::$Current->RestoreOrRegisterManifestDI();

        return self::$Current;
    }

    public static Hydro2 $Current;

    private AutoloadService $_autoloadService;
    private PackageManifestService $_manifestService;
    private RouterService $_routerService;
    private array $diClasses = [];

    public function __construct(
        public string $WebDir,
        public string $MainAppDir,
        public array $AdditionalAppDirs = [],
    )
    {
        date_default_timezone_set("UTC");
    }

    public function RegisterAutoloader() : void
    {
        // set up autoloader
        require_once $this->MainAppDir."/Pivel/Hydro2/Services/AutoloadService.php";
        
        $this->_autoloadService = new AutoloadService($this->MainAppDir);
        foreach ($this->AdditionalAppDirs as $additionalAppDir) {
            $this->_autoloadService->AddDir($additionalAppDir);
        }

        $this->_autoloadService->Register();
    }

    public function RegisterSingleton(string $class, ?string $interface = null) : void
    {
        $classOrInterface = $interface??$class;
        $this->diClasses[$classOrInterface] = [
            'class' => $class,
            'isSingleton' => true,
            'instance' => null,
        ];
    }

    public function RegisterTransient(string $class, ?string $interface = null) : void
    {
        $classOrInterface = $interface??$class;
        $this->diClasses[$classOrInterface] = [
            'class' => $class,
            'isSingleton' => false,
            'instance' => null,
        ];
    }

    /**
     * @param T $classOrInterface
     * The name of the class or interface to resolve. Returns null if not registered.
     * @param mixed[] $args
     * Array of args to pass (unpacked) to class' constructor after other dependencies are passed.
     */
    public function ResolveDependency(string $classOrInterface, array $args = []) : ?object
    {
        if (!isset($this->diClasses[$classOrInterface])) {
            return null;
        }

        if ($this->diClasses[$classOrInterface]['isSingleton'] && $this->diClasses[$classOrInterface]['instance'] !== null) {
            return $this->diClasses[$classOrInterface]['instance'];
        }
        
        // need to use reflection to find a list of the class or interface's constructor's arguments
        $dependencyArgs = [];
        $rc = new ReflectionClass($this->diClasses[$classOrInterface]['class']);
        $constructor = $rc->getConstructor();
        if ($constructor != null) {
            $parameters = $constructor->getParameters();
            foreach ($parameters as $parameter) {
                $type = $parameter->getType();
                if (!($type instanceof ReflectionNamedType)) {
                    break;
                }

                $class = $type->getName();
                if (!isset($this->diClasses[$class])) {
                    break;
                }

                // TODO prevent circular dependency
                $dependencyArgs[] = $this->ResolveDependency($class);
            }
        }

        $instance = new $this->diClasses[$classOrInterface]['class'](...$dependencyArgs, ...$args);

        if ($this->diClasses[$classOrInterface]['isSingleton']) {
            $this->diClasses[$classOrInterface]['instance'] = $instance;
        }
        
        return $instance;
    }

    public function ResolveManifestService(string $class) : void
    {
        $this->_manifestService = $this->ResolveDependency($class);
    }
    
    public function ResolveRouterService(string $class) : void
    {
        $this->_routerService = $this->ResolveDependency($class);
    }

    public function RestoreOrRegisterManifestDI() : void
    {
        // TODO save this and load it from file on future requests
        // parse through all ['controllers'] and ['services'] in all package manifests, and register them all with DI
        $pkg_manifest = $this->_manifestService->GetPackageManifest();
        foreach ($pkg_manifest as $vendor_pkgs) {
            foreach ($vendor_pkgs as $pkg_info) {
                if (isset($pkg_info['controllers'])) {
                    foreach ($pkg_info['controllers'] as $c) {
                        $this->RegisterSingleton($c);
                    }
                }

                if (isset($pkg_info['services'])) {
                    foreach ($pkg_info['services'] as $c) {
                        $this->RegisterSingleton($c['class'], $c['interface']??null);
                    }
                }
            }
        }
    }
    
    public function buildRequest()
    {
        $sapi_name = php_sapi_name();
        
        // Returns Request
        return new Request($_SERVER, $_COOKIE, $_POST, $_GET, $sapi_name);
    }
    
    public function processRequest(Request $request)
    {
        // try loading pre-built routing table
        //$loading_start = microtime(true);
        $could_load = $this->_routerService->LoadRoutes();
        //$loading_end = microtime(true);
        //echo 'Took ' . ($loading_end - $loading_start) * 1000 . 'ms to load existing routing table.';

        if (!$could_load) {
            // routing table hasn't been built yet, so build it
            //$parsing_start = microtime(true);
            $this->_routerService->RegisterRoutesFromAttributes();
            //$parsing_end = microtime(true);
            //echo 'Took ' . ($parsing_end - $parsing_start) * 1000 . 'ms to build a new routing table.';
            $this->_routerService->SaveRoutes();
        }

        // search for match(es) in routing table
        $matched_routes = $this->_routerService->GetMatchingRoutes($request->method, $request->getEndpoint());
        //echo 'Matching routes: <pre>' . print_r($matched_routes, true) . '</pre>';

        $response = new Response();
        $response->setFinal(false);
        // For each matched route, parse the incoming path, initialize the controller and call the indicated method.
        // A method might return something to indicate that the next method should be used instead.
        foreach ($matched_routes as $matched_route) {
            $parameters = $this->_routerService::ParsePathParameters($matched_route['path'], $request->getEndpoint());
            //echo 'Parameters: <pre>' . print_r($parameters, true) . '</pre>';

            $request->Args = array_merge($request->Args, $parameters);

            $controller = $this->ResolveDependency($matched_route['controller_class'], [$request]);
            $method_name = $matched_route['controller_method'];
            
            //try {
                $result = $controller->$method_name();
            /*} catch (Exception $e) {
                $result = new JsonResponse(
                    [
                        'exception' => [
                            'message' => $e->getMessage(),
                            'code' => $e->getCode(),
                            'controller' => $matched_route['controller_class'],
                            'method' => $method_name,
                        ],
                    ],
                    StatusCode::InternalServerError,
                    "An exception occurred while executing a route handler."
                );
            }*/

            if ($result instanceof Response) {
                $response->append($result);
            }
            
            if ($response->isFinal()) {
                break;
            }
        }

        // Returns Response
        return $response;
    }

    public function Run() : self
    {
        // Process incoming request
        $request = $this->buildRequest();
        $response = $this->processRequest($request);
        $response->send(false);

        return $this;
    }
    
    public function Dispose()
    {
    }
}