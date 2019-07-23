<?php

namespace Package;

use \Package\CCMS\Response;
use \Package\CCMS\Request;
use \Package\CCMS\Utilities;
use \Package\Database\Setup;
use \PDO;
use \PDOException;

class Database extends PDO
{
    // Singleton pattern
    private static $instance = null;
    
    public static function Instance()
    {
        if (!self::$instance instanceof Database) {
            self::$instance = new static();
        }
        
        return self::$instance;
    }
    
    
    private $connectionOpen = false;
    private $connectionStatus = "";
    private $connection = null;
    
    public function __construct($configFile="")
    {
        if (!file_exists($configFile)) {
            $configFile = dirname(__FILE__) . "/config.ini";
        }

        if (!file_exists($configFile)) {
            $this->connectionStatus = "Configuration file missing";
            return;
        }
        
        $db_config = parse_ini_file($configFile, true);

        try {
            parent::__construct("{$db_config["driver"]}:host=" . $db_config["host"] . ";dbname=" . $db_config["database"]["primary"], $db_config["account"]["username"], $db_config["account"]["password"]);
            $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connectionOpen = true;
        } catch(PDOException $e) {
            $this->connectionStatus = $e->getMessage();
        }
    }
    
    public function isConnectionOpen()
    {
        return $this->connectionOpen;
    }
    
    public function getConnectionStatus()
    {
        return $this->connectionStatus;
    }

    public function execute($statement)
    {
        $s = $this->prepare($statement);
        $s->execute();$s->setFetchMode(PDO::FETCH_ASSOC);
        try {
            return $s->fetchAll();
        } catch(PDOException $e) {
            
        }
    }

    public static function hookOpenConnection(Request $request)
    {
        $instance = self::Instance();

        if ($instance->isConnectionOpen()) {
            return;
        }
        
        if ($instance->connectionStatus !== "Configuration file missing") {
            return new Response("No database connection. Reason:\n" . $instance->getConnectionStatus());
        }

        return Setup::hookConfiguration($request);
    }
}