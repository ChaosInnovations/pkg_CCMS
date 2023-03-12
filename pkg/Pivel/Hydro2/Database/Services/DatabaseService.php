<?php

namespace Package\Pivel\Hydro2\Database\Services;

use Package\Pivel\Hydro2\Database\Controllers\IDatabaseProvider;
use Package\Pivel\Hydro2\Database\Controllers\MySQLDatabaseProvider;
use Package\Pivel\Hydro2\Database\Controllers\SqliteDatabaseProvider;
use Package\Pivel\Hydro2\Database\Extensions\Exceptions\HostNotFoundException;
use Package\Pivel\Hydro2\Database\Extensions\Exceptions\InvalidConfigurationException;
use Package\Pivel\Hydro2\Database\Extensions\Exceptions\InvalidUserException;
use Package\Pivel\Hydro2\Database\Models\DatabaseConfigurationProfile;
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
        $config = DatabaseConfigurationProfile::LoadFromKey($configurationKey);

        if ($config === null) {
            throw new InvalidConfigurationException("Configuration '{$configurationKey}' couldn't be loaded.", 1);
        }
        
        $this->databaseProvider = self::GetDatabaseProvider($config);

        if (!($this->databaseProvider instanceof IDatabaseProvider)) {
            throw new InvalidConfigurationException("Configuration \"{$configurationKey}\" is invalid.", 2);
        }

        $this->connectionOpen = $this->databaseProvider->OpenConnection();
    }

    public static function GetDatabaseProvider(DatabaseConfigurationProfile $profile) : ?IDatabaseProvider {
        // string $driver, string $host, ?string $database, ?string $username, ?string $password) : null|IDatabaseProvider {
        if (!isset(self::$databaseProviders[$profile->Driver])) {
            return null;
        }

        $providerName = self::$databaseProviders[$profile->Driver];
        return new $providerName($profile);
    }

    public function IsConnectionOpen() : bool {
        return $this->connectionOpen;
    }

    /** @return string[] */
    public static function GetAvailableDrivers() : array {
        return array_filter(PDO::getAvailableDrivers(),fn($d)=>in_array($d, array_keys(self::$databaseProviders)));
    }

    public static function CheckDriver(string $driver) : bool {
        return in_array($driver, self::GetAvailableDrivers());
    }

    public static function CheckHost(string $driver, string $host) : bool {
        $dbp = self::GetDatabaseProvider(new DatabaseConfigurationProfile('', $driver, $host));
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
        $dbp = self::GetDatabaseProvider(new DatabaseConfigurationProfile('', $driver, $host, $username, $password));
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
        $dbp = self::GetDatabaseProvider(new DatabaseConfigurationProfile('', $driver, $host, $username, $password));
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

    public static function CreateDatabase(string $driver, string $host, ?string $username, ?string $password, ?string $database) : bool {
        $dbp = self::GetDatabaseProvider(new DatabaseConfigurationProfile('', $driver, $host, $username, $password));
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

        if (!$dbp->CanCreateDatabases($username)) {
            return false;
        }

        if ($database === null) {
            // might be a sqlite db, be permissive and pretend that we created the database.
            return true;
        }

        return $dbp->CreateDatabase($database);
    }
}