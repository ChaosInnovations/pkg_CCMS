<?php

namespace Package\Database;

use \Package\CCMS\Response;
use \Package\CCMS\Request;
use \Package\CCMS\Utilities;
use \Package\Database;
use \PDO;
use \PDOException;

class Setup
{
    public static function hookConfiguration(Request $request)
    {
        if (preg_match('/^web:\/?api\/database\/setup\/connect\/?$/i', $request->getTypedEndpoint())) {
            return new Response(self::checkSettings());
        }

        if (preg_match('/^web:\/?api\/database\/setup\/select\/?$/i', $request->getTypedEndpoint())) {
            return new Response(self::selectDatabase());
        }

        if (preg_match('/^web:\/?api\/database\/setup\/create\/?$/i', $request->getTypedEndpoint())) {
            return new Response(self::selectDatabase(true));
        }

        if (preg_match('/(?!.*\.[a-z]*$)^web:.*$/i', $request->getTypedEndpoint())) {
            return new Response(self::getSetupInterface());
        }

        return new Response();
    }

    public static function getSetupInterface()
    {
        $driverList = "";
        $selected = false;
        foreach (PDO::getAvailableDrivers() as $driver) {
            $driverList .= "<option value=\"{$driver}\" " . (!$selected ? "selected=\"selected\"" : "") . ">{$driver}</option>";
            $selected = true;
        }

        $template_vars = [
            'driverList' => $driverList,
        ];

        $content = Utilities::fillTemplate(file_get_contents(dirname(__FILE__) . "/SetupInterface.template.html"), $template_vars);

        return $content;
    }

    public static function checkSettings()
    {
        $connectionOpen = false;
        $connectionStatus = "";
        $hasFullPermissions = false;
        $hasEnoughPermissions = false;
        $databases = [];
        try {
            $settings = json_decode($_POST["settings"], true);
            $testConnection = new PDO("{$_POST['driver']}:host={$settings['host']};", $settings["username"], $settings["password"]);
            $testConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $connectionOpen = true;

            $stmt = $testConnection->prepare("SHOW DATABASES;");
            $stmt->execute();
            $dbs = $stmt->fetchAll();
            foreach ($dbs as $db) {
                array_push($databases, $db['Database']);
            }

            $stmt = $testConnection->prepare("SHOW GRANTS FOR CURRENT_USER;");
            $stmt->execute();
            $grants = $stmt->fetchAll();
            foreach ($grants as $grant) {
                if (strpos($grant[0], "GRANT ALL PRIVILEGES ON *.*") === 0) {
                    $hasFullPermissions = true;
                    $hasEnoughPermissions = true;
                    break;
                }
            }
        } catch(PDOException $e) {
            $connectionStatus = $e->getMessage();
        }

        $result = [
            'connection' => $connectionOpen,
            'status' => $connectionStatus,
            'hasFullPermissions' => $hasFullPermissions,
            'hasEnoughPermissions' => $hasEnoughPermissions,
            'databases' => $databases,
        ];

        return json_encode($result);
    }

    public static function selectDatabase($create = false)
    {
        $connectionOpen = false;
        $connectionStatus = "";
        $success = false;
        try {
            $settings = json_decode($_POST["settings"], true);
            $testConnection = new PDO("{$_POST['driver']}:host={$settings['host']};", $settings["username"], $settings["password"]);
            $testConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $connectionOpen = true;

            if ($create) {
                $stmt = $testConnection->prepare("CREATE DATABASE IF NOT EXISTS `{$settings["database"]}`;");
                $stmt->execute();
            }

            $stmt = $testConnection->prepare("USE `{$settings["database"]}`;");
            $stmt->execute();

            $success = true;

            $template_vars = [
                'driver' => $_POST['driver'],
                'hostname' => $settings['host'],
                'username' => $settings['username'],
                'password' => $settings['password'],
                'database' => $settings['database'],
            ];

            $configContents = Utilities::fillTemplate(file_get_contents(dirname(__FILE__) . "/config.template.ini"), $template_vars);

            file_put_contents(dirname(__FILE__) . "/config.ini", $configContents);
        } catch(PDOException $e) {
            $connectionStatus = $e->getMessage();
        }

        $result = [
            'connection' => $connectionOpen,
            'status' => $connectionStatus,
            'success' => $success,
        ];

        return json_encode($result);
    }
}