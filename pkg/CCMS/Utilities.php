<?php

namespace Package\CCMS;

use \DirectoryIterator;
use \PDO;

class Utilities
{
    public static $pkg_manifest = null;
    public static function getPackageManifest() : array {
        if (self::$pkg_manifest === null) {
            self::$pkg_manifest = [];

            $pkgDirs = [
                $_SERVER["DOCUMENT_ROOT"] . "/pkg",
            ];
        
            foreach ($pkgDirs as $searchDir) {
                $dir = new DirectoryIterator($searchDir);
                foreach ($dir as $fileinfo) {
                    if ($fileinfo->isDot()) {
                        continue;
                    }
                    if (!$fileinfo->isDir()) {
                        continue;
                    }
                
                    if (!file_exists($searchDir . "/" . $fileinfo->getFilename() . "/manifest.json")) {
                        continue;
                    }
                
                    if (!is_file($searchDir . "/" . $fileinfo->getFilename() . "/manifest.json")) {
                        continue;
                    }
                
                    $manifest = json_decode(file_get_contents($searchDir . "/" . $fileinfo->getFilename() . "/manifest.json"), true);

                    self::$pkg_manifest[$fileinfo->getFilename()] = $manifest;
                    self::$pkg_manifest[$fileinfo->getFilename()]["dependencies"]["has_dependent"] = false;
                }
            }

            // Prevent CCMSIndex from being uninstalled
            self::$pkg_manifest["CCMSIndex"]["dependencies"]["has_dependent"] = true;
            // Check dependencies
            $missing_dependencies = false;
            do {
                foreach (self::$pkg_manifest as $module_name => $module_info) {
                    $dependencies = array_merge($module_info["dependencies"]["libraries"], $module_info["dependencies"]["modules"]);
                    if (count($dependencies) === 0) {
                        $missing_dependencies = false;
                        continue;
                    }

                    foreach ($dependencies as $index => $dependency) {
                        if (!isset(self::$pkg_manifest[$dependency["name"]])) {
                            echo "Module \"{$module_name}\" missing dependency \"{$dependency["name"]}\"<br />\n";
                            $missing_dependencies = true;
                            break;
                        }

                        self::$pkg_manifest[$dependency["name"]]["dependencies"]["has_dependent"] = true;

                        $minVer = $dependency["min_version"];
                        $depVer = self::$pkg_manifest[$dependency["name"]]["module_data"]["version"];

                        $cmp = 8 * ($depVer[0] <=> $minVer[0]);
                        $cmp += 4 * ($depVer[1] <=> $minVer[1]);
                        $cmp += 2 * ($depVer[2] <=> $minVer[2]);
                        $cmp += 1 * ($depVer[3] <=> $minVer[3]);

                        $minVerStr = implode(".", $minVer);
                        $depVerStr = implode(".", $depVer);

                        if ($cmp < 0) {
                            echo "Module \"{$module_name}\" requires dependency \"{$dependency["name"]}\" to be at least version {$minVerStr}, ";
                            echo "and \"{$dependency["name"]}\" is only version {$depVerStr}<br />\n";
                            $missing_dependencies = true;
                            break;
                        }
                        
                        $missing_dependencies = false;
                    }

                    if ($missing_dependencies) {
                        unset(self::$pkg_manifest[$module_name]);
                        break;
                    }
                }
            } while ($missing_dependencies);

        }

        return self::$pkg_manifest;
    }

    public static function fillTemplate(string $template, array $vars)
    {
        foreach($vars as $k=>$v){
            $template = str_replace('{'.$k.'}', $v, $template);
        }
        return $template;
    }

    public static function setMaintenanceMode(bool $enable)
    {
        if ($enable) {
            unlink($_SERVER["DOCUMENT_ROOT"] . "/.htaccess");
            copy($_SERVER["DOCUMENT_ROOT"] . "/.htaccess.maintenance", $_SERVER["DOCUMENT_ROOT"] . "/.htaccess");
        } else {
            unlink($_SERVER["DOCUMENT_ROOT"] . "/.htaccess");
            copy($_SERVER["DOCUMENT_ROOT"] . "/.htaccess.production", $_SERVER["DOCUMENT_ROOT"] . "/.htaccess");
        }
    }
}