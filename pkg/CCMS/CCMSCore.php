<?php

namespace Package\CCMS;

use \Exception;
use \Package\CCMS\Request;
use \Package\CCMS\Response;
use \Package\CCMS\Utilities;

class CCMSCore
{
    public function __construct()
    {
        // Delete setup script and STATE if it still exists (first time launch)
        if (file_exists("STATE") && file_get_contents("STATE") == "5:0") {
            // Remove setup files
            unlink("setup.php");
            unlink("STATE");
        }
        
        date_default_timezone_set("UTC");
    }
    
    public function buildRequest()
    {
        $sapi_name = php_sapi_name();
        
        // Returns Request
        return new Request($_SERVER, $_COOKIE, $sapi_name);
    }
    
    public function processRequest(Request $request)
    {
        $response = new Response();
        $response->setFinal(false);

        $hooks = [];

        foreach (Utilities::getPackageManifest() as $module_name => $module_manifest) {
            if (!isset($module_manifest["routes"])) {
                continue;
            }
        
            $routes = $module_manifest["routes"];
        
            foreach ($routes as $route) {
                array_push($hooks, [$route["hook"], $route["target"], $route["rank"]]);
            }
        }

        usort($hooks, function($a, $b) { return ($a[2] <=> $b[2]); });

        // Enumerate hooks
        foreach ($hooks as $hook) {
            $hookRegex = $hook[0];
            $hookFunctionName = $hook[1];
            if (!preg_match($hookRegex, $request->getTypedEndpoint())) {
                continue;
            }
            
            $result = null;
            
            try {
                $result = $hookFunctionName($request, $response);
            } catch (Exception $e) {
                $response->append(new Response($e));
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