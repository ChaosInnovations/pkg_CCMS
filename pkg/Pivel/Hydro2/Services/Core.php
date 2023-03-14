<?php

namespace Package\Pivel\Hydro2\Services;

use \Exception;
use Package\Pivel\Hydro2\Core\Models\HTTP\StatusCode;
use Package\Pivel\Hydro2\Core\Models\JsonResponse;
use \Package\Pivel\Hydro2\Core\Models\Request;
use \Package\Pivel\Hydro2\Core\Models\Response;

class Core
{
    private Router $router;
    public function __construct()
    {
        // Delete setup script and STATE if it still exists (first time launch)
        if (file_exists("STATE") && file_get_contents("STATE") == "5:0") {
            // Remove setup files
            unlink("setup.php");
            unlink("STATE");
        }
        
        date_default_timezone_set("UTC");

        $this->router = new Router();
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
        $could_load = $this->router->LoadRoutes();
        //$loading_end = microtime(true);
        //echo 'Took ' . ($loading_end - $loading_start) * 1000 . 'ms to load existing routing table.';

        if (!$could_load) {
            // routing table hasn't been built yet, so build it
            //$parsing_start = microtime(true);
            $this->router->RegisterRoutesFromAttributes();
            //$parsing_end = microtime(true);
            //echo 'Took ' . ($parsing_end - $parsing_start) * 1000 . 'ms to build a new routing table.';
            $this->router->SaveRoutes();
        }

        // search for match(es) in routing table
        $matched_routes = $this->router->GetMatchingRoutes($request->method, $request->getEndpoint());
        //echo 'Matching routes: <pre>' . print_r($matched_routes, true) . '</pre>';

        $response = new Response();
        $response->setFinal(false);
        // For each matched route, parse the incoming path, initialize the controller and call the indicated method.
        // A method might return something to indicate that the next method should be used instead.
        foreach ($matched_routes as $matched_route) {
            $parameters = Router::ParsePathParameters($matched_route['path'], $request->getEndpoint());
            //echo 'Parameters: <pre>' . print_r($parameters, true) . '</pre>';

            $request->Args = array_merge($request->Args, $parameters);

            $controller = new $matched_route['controller_class']($request);
            $method_name = $matched_route['controller_method'];
            
            try {
                $result = $controller->$method_name();
            } catch (Exception $e) {
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
            }

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
    
    public function dispose()
    {
    }
}