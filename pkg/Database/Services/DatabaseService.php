<?php

namespace Package\Database\Services;

use Package\Database\Controllers\IDatabaseProvider;
use Package\Database\Controllers\MySQLDatabaseProvider;
use PDO;

class DatabaseService
{
    private IDatabaseProvider $databaseProvider;

    // Singleton pattern
    private static null|DatabaseService $instance = null;
    
    public static function Instance() : DatabaseService {
        if (!self::$instance instanceof DatabaseService) {
            self::$instance = new static();
        }
        
        return self::$instance;
    }

    private array $config;
    private $connectionOpen = false;
    private $connectionStatus = "";
    
    public function __construct() {
        if (!$this->CheckConfiguration()) {
            return;
        }

        if ($this->config['driver'] == 'mysql') {
            $this->databaseProvider = new MySQLDatabaseProvider(
                $this->config['host'],
                $this->config['database'],
                $this->config['username'],
                $this->config['password'],
            );
        }

        $this->connectionOpen = $this->databaseProvider->OpenConnection();
    }

    public function IsConnectionOpen() : bool {
        return $this->connectionOpen;
    }

    public function CheckConfiguration() : bool {
        if (!file_exists(dirname(__FILE__, 2) . '/config.json')) {
            $this->connectionStatus = "Configuration file missing";
            return false;
        }

        $raw_config = file_get_contents(dirname(__FILE__, 2) . '/config.json');
        $this->config = json_decode($raw_config, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            $this->config = [];
            return false;
        }

        return true;
    }

    public static function GetAvailableDrivers() : array {
        return PDO::getAvailableDrivers();
    }

    public static function CheckDriver(string $driver) : bool {
        return in_array($driver, PDO::getAvailableDrivers());
    }

}