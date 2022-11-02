<?php

namespace Package\Database\Services;

use Package\Database\Controllers\IDatabaseProvider;
use Package\Database\Controllers\MySQLDatabaseProvider;
use PDO;
use PDOException;

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

    public static function CheckHost(string $driver, string $host) : bool {
        $dsn = "{$driver}:host={$host}";
        if ($driver == 'sqlite') {
            // for sqlite, the "host" should be the path to the SQLite 3 database.
            $dsn = "sqlite:{$host}";
        }
        try {
            $p = new PDO($dsn);
            $p->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // if there wasn't an error, then this is probably an sqlite or similar that doesn't use a username/password
            // or: the username/password could have been injected at the end of $host - in thi case, the lack of an error
            // would still indicate a valid host/path.
            return true;
        } catch(PDOException $e) {
            // check that error message only indicates that the user/password is incorrect.
            // any other error, the driver is wrong or the database host is not accessible.
            if ($driver == 'mysql' && $e->errorInfo[1] == 1045) {
                return true;
            }
            return false;
        }
    }
}