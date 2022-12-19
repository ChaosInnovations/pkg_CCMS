<?php

namespace Package\Database\Services;

use Package\Database\Controllers\IDatabaseProvider;
use Package\Database\Controllers\MySQLDatabaseProvider;
use Package\Database\Extensions\Exceptions\HostNotFoundException;
use Package\Database\Extensions\Exceptions\InvalidUserException;
use PDO;
use PDOException;

class DatabaseService
{
    public IDatabaseProvider $databaseProvider;

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

    private static array $databaseProviders = [
        'mysql' => MySQLDatabaseProvider::class,
    ];
    
    public function __construct() {
        if (!$this->LoadConfiguration()) {
            return;
        }

        $this->databaseProvider = self::GetDatabaseProvider(
            $this->config['driver'],
            $this->config['host'],
            $this->config['database'],
            $this->config['username'],
            $this->config['password'],
        );
        if (!($this->databaseProvider instanceof IDatabaseProvider)) {
            return;
        }

        $this->connectionOpen = $this->databaseProvider->OpenConnection();
    }

    public static function GetDatabaseProvider(string $driver, string $host, ?string $database, ?string $username, ?string $password) : null|IDatabaseProvider {
        if (!isset(self::$databaseProviders[$driver])) {
            return null;
        }

        $providerName = self::$databaseProviders[$driver];
        return new $providerName($host, $database, $username, $password);
    }

    public function IsConnectionOpen() : bool {
        return $this->connectionOpen;
    }

    public function LoadConfiguration() : bool {
        if (!file_exists(dirname(__FILE__, 2) . '/config.json')) {
            $this->connectionStatus = "Configuration file missing";
            return false;
        }

        $raw_config = file_get_contents(dirname(__FILE__, 2) . '/config.json');
        $c = json_decode($raw_config, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            $this->config = [];
            return false;
        }
        $this->config = $c['primary'];

        return true;
    }

    public static function UpdateConfiguration(string $driver, string $host, ?string $username, ?string $password, ?string $database) {
        $config = [
            'primary' =>[
                'driver' => $driver,
                'host' => $host,
                'username' => $username,
                'password' => $password,
                'database' => $database,
            ],
        ];

        file_put_contents(dirname(__FILE__, 2) . '/config.json', json_encode($config));

        // if the database configuration is being changed, should database content be migrated?
    }

    /** @return string[] */
    public static function GetAvailableDrivers() : array {
        return array_filter(PDO::getAvailableDrivers(),fn($d)=>in_array($d, array_keys(self::$databaseProviders)));
    }

    public static function CheckDriver(string $driver) : bool {
        return in_array($driver, self::GetAvailableDrivers());
    }

    public static function CheckHost(string $driver, string $host) : bool {
        $dbp = self::GetDatabaseProvider($driver, $host, null, null, null);
        if (!($dbp instanceof IDatabaseProvider)) {
            return false;
        }
        try {
            $dbp->OpenConnection();
        } catch (HostNotFoundException) {
            return false;
        } catch (InvalidUserException) {
            return true;
        } catch(PDOException $e) {
            return true;
        }
        return true;
    }

    public static function GetPrivileges(string $driver, string $host, ?string $username, ?string $password) : bool|array {
        $dbp = self::GetDatabaseProvider($driver, $host, null, $username, $password);
        if (!($dbp instanceof IDatabaseProvider)) {
            return false;
        }
        try {
            $dbp->OpenConnection();
        } catch (HostNotFoundException) {
            return false;
        } catch (InvalidUserException) {
            return false;
        } catch(PDOException $e) {
            return false;
        }

        $canCreate = $dbp->CanCreateDatabases();
        return ['valid'=>true,'cancreatedb'=>$canCreate];
    }

    public static function GetDatabases(string $driver, string $host, ?string $username, ?string $password) : bool|array {
        $dbp = self::GetDatabaseProvider($driver, $host, null, $username, $password);
        if (!($dbp instanceof IDatabaseProvider)) {
            return false;
        }
        try {
            $dbp->OpenConnection();
        } catch (HostNotFoundException) {
            return false;
        } catch (InvalidUserException) {
            return false;
        } catch(PDOException $e) {
            return false;
        }

        return $dbp->GetDatabases();
    }

    public static function CreateDatabase(string $driver, string $host, ?string $username, ?string $password, string $database) : bool {
        $dbp = self::GetDatabaseProvider($driver, $host, null, $username, $password);
        if (!($dbp instanceof IDatabaseProvider)) {
            return false;
        }
        try {
            $dbp->OpenConnection();
        } catch (HostNotFoundException) {
            return false;
        } catch (InvalidUserException) {
            return false;
        } catch(PDOException $e) {
            return false;
        }

        if (!$dbp->CanCreateDatabases()) {
            return false;
        }

        return $dbp->CreateDatabase($database);
    }
}