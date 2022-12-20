<?php

namespace Package\Database\Services;

use Package\Database\Controllers\IDatabaseProvider;
use Package\Database\Controllers\MySQLDatabaseProvider;
use Package\Database\Controllers\SqliteDatabaseProvider;
use Package\Database\Extensions\Exceptions\HostNotFoundException;
use Package\Database\Extensions\Exceptions\InvalidConfigurationException;
use Package\Database\Extensions\Exceptions\InvalidUserException;
use PDO;
use PDOException;

class DatabaseService
{
    public IDatabaseProvider $databaseProvider;

    // Singleton pattern
    private static null|DatabaseService $instance = null;
    
    public static function Instance() : ?DatabaseService {
        if (!self::$instance instanceof DatabaseService) {
            try {
                self::$instance = new static();
            } catch (InvalidConfigurationException) {
                self::$instance = null;
            } catch (HostNotFoundException) {
                self::$instance = null;
            } catch (InvalidUserException) {
                self::$instance = null;
            }
        }
        
        return self::$instance;
    }

    public static function IsPrimaryConnected() : bool {
        return (self::Instance() instanceof DatabaseService && self::Instance()->IsConnectionOpen());
    }

    private array $config;
    private $connectionOpen = false;

    private static array $databaseProviders = [
        'mysql' => MySQLDatabaseProvider::class,
        'sqlite' => SqliteDatabaseProvider::class,
    ];
    
    public function __construct(string $configurationKey='primary') {
        $config = self::LoadConfiguration($configurationKey);
        if ($config == false) {
            if ($configurationKey != 'primary') {
                throw new InvalidConfigurationException("Configuration '{$configurationKey}' couldn't be loaded.", 1);
            }

            // if primary not configured yet, set default.sqlite3 as the default.
            self::UpdateConfiguration('sqlite', 'default.sqlite3', null, null, null, $configurationKey);
            $config = self::LoadConfiguration($configurationKey);
        }

        $this->databaseProvider = self::GetDatabaseProvider(
            $config['driver'],
            $config['host'],
            $config['database'],
            $config['username'],
            $config['password'],
        );

        if (!($this->databaseProvider instanceof IDatabaseProvider)) {
            throw new InvalidConfigurationException("Configuration \"{$configurationKey}\" is invalid.", 2);
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

    public static function LoadConfiguration(string $configurationKey) : bool|array {
        if (!file_exists(dirname(__FILE__, 2) . '/config.json')) {
            return false;
        }

        $raw_config = file_get_contents(dirname(__FILE__, 2) . '/config.json');
        /** @var array[] */
        $c = json_decode($raw_config, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            return false;
        }

        return $c[$configurationKey]??[];
    }

    public static function UpdateConfiguration(string $driver, string $host, ?string $username, ?string $password, ?string $database, string $configurationKey='primary') {
        $config = [];

        if (file_exists(dirname(__FILE__, 2) . '/config.json')) {
            $raw_config = file_get_contents(dirname(__FILE__, 2) . '/config.json');
            $config = json_decode($raw_config, true);
            if (json_last_error() != JSON_ERROR_NONE) {
                $config = [];
            }
        }

        $config[$configurationKey] = [
            'driver' => $driver,
            'host' => $host,
            'username' => $username,
            'password' => $password,
            'database' => $database,
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