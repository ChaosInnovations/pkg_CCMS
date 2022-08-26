<?php

namespace Package\CCMS\Controllers;

use ReflectionClass;

class Router
{
    private array $routes;

    public function __construct() {

    }

    public function RegisterRoutesFromAttributes() : void {
        // Get list of controllers from Utilities\getPackageManifest()
        $c = \Package\Controller::class;

        $class = new ReflectionClass($c);

        $route_prefix_segments = [];
        $class_attributes = $class->getAttributes(RoutesPrefix::class);
        foreach ($class_attributes as $class_attribute) {
            $route_prefix = $class_attribute->newInstance();
            $prefix = $route_prefix->pathPrefix;
            $prefix = trim($prefix, "/");
            $route_prefix_segments = explode("/", $prefix);
        }

        foreach ($class->getMethods() as $method) {
            $attributes = $method->getAttributes(Route::class);

            foreach ($attributes as $attribute) {
                $route = $attribute->newInstance();
                $use_prefix = !str_starts_with($route->path, '~');
                if (!$use_prefix) {
                    $route->path = substr($route->path, 1);
                }
                $path = trim($route->path, '/');
                $route_segments = explode('/', $path);
                $this->routes[] = [
                    'method' => $route->method,
                    'path' => ($use_prefix ? array_merge($route_prefix_segments, $route_segments) : $route_segments),
                    'controller_class' => $c,
                    'controller_method' => $method->getName(),
                    'order' => $route->order,
                ];
            }
        }

        $this->SortRoutes();
    }

    public function LoadRoutes() : bool {
        if (!file_exists('routes.json')) {
            return false;
        }

        $loading_start = microtime(true);
        $raw_routes = file_get_contents('routes.json');
        $loading_end = microtime(true);
        echo "Took " . ($loading_end - $loading_start) * 1000 . 'ms to load file contents.';
        $this->routes = json_decode($raw_routes, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            $this->routes = [];
            return false;
        }

        return true;
    }

    public function SaveRoutes() : void {
        file_put_contents('routes.json', json_encode($this->routes));
    }

    public function GetMatchingRoutes(string $method, string $path) : array {

    }

    /**
     * Sorts the routing table as follows:
     *  1. Compare order property (lower gets evaluated first)
     *  2. If order matches, compare segment types in order:
     *      a) literal ("/segment/")
     *      b) contrained parameter ("/{param:int}/")
     *      c) unconstrained parameter ("/{param}/")
     *      d) contrained wildcard parameter ("/{*param:date}/")
     *      e) unconstrained wildcard parameter ("/{*param}/")
     *  3. If still a tie, compare template string (without leading or trailing '/' or leading '~')
     */

    private function SortRoutes() : void {
        usort($this->routes, [self::class, 'RouteCmp']);
    }

    public static function RouteCmp($a, $b) {
        // 1.
        if ($a['order'] != $b['order']) {
            return $a['order'] <=> $b['order'];
        }

        // 2.
        for ($i = 0; $i < min(count($a['path']), count($b['path'])); $i++){
            $a_type = self::GetPathSegmentType($a['path'][$i]);
            $b_type = self::GetPathSegmentType($b['path'][$i]);
            if ($a_type != $b_type) {
                return $a_type <=> $b_type;
            }
        }

        // 3.
        // Leading/trailing '/' and leading '~' were already stripped when bulding the routing table.
        return strcmp(join('/', $a['path']), join('/', $b['path']));
    }

    const SEG_LITERAL = 0;
    const SEG_PARAM_CONSTRAINED = 1;
    const SEG_PARAM_UNCONSTRAINED = 2;
    const SEG_WILD_PARAM_CONSTRAINED = 3;
    const SEG_WILD_PARAM_UNCONSTRAINED = 4;

    public static function GetPathSegmentType(string $segment) : int {
        if (str_starts_with($segment, '{*') && str_ends_with($segment, '}')) {
            // Wildcard parameter. Add check for constraints later.
            return self::SEG_WILD_PARAM_UNCONSTRAINED;
        }

        if (str_starts_with($segment, '{') && str_ends_with($segment, '}')) {
            // Parameter. Add check for constraints later.
            return self::SEG_PARAM_UNCONSTRAINED;
        }

        return self::SEG_LITERAL;
    }

    public static function ParsePathParameters(array $template, string $path) : array {

    }
}