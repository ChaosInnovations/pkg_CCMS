<?php

namespace Pivel\Hydro2\Services\Database;

use Pivel\Hydro2\Exceptions\Database\InvalidConfigurationException;
use Pivel\Hydro2\Exceptions\Database\HostNotFoundException;
use Pivel\Hydro2\Exceptions\Database\InvalidUserException;
use Pivel\Hydro2\Models\Database\DatabaseConfigurationProfile;
use PDO;
use PDOException;

class DatabaseService
{
    public IDatabaseProvider $databaseProvider;

    public function IsPrimaryConnected() : bool {
        return $this->IsConnectionOpen();
    }

    private array $config;
    private $connectionOpen = false;

    private static array $databaseProviders = [
        'mysql' => MySQLDatabaseProvider::class,
        'sqlite' => SqliteDatabaseProvider::class,
    ];
    
    public function __construct() {
        $configurationKey='primary';
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

    public function GetDatabaseProvider(DatabaseConfigurationProfile $profile) : ?IDatabaseProvider {
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
    public function GetAvailableDrivers() : array {
        return array_filter(PDO::getAvailableDrivers(),fn($d)=>in_array($d, array_keys(self::$databaseProviders)));
    }

    public function CheckDriver(string $driver) : bool {
        return in_array($driver, $this->GetAvailableDrivers());
    }

    public function CheckHost(string $driver, string $host) : bool {
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

    public function GetPrivileges(string $driver, string $host, ?string $username, ?string $password) : bool|array {
        $dbp = $this->GetDatabaseProvider(new DatabaseConfigurationProfile('', $driver, $host, $username, $password));
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

    public function GetDatabases(string $driver, string $host, ?string $username, ?string $password) : bool|array {
        $dbp = $this->GetDatabaseProvider(new DatabaseConfigurationProfile('', $driver, $host, $username, $password));
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

    public function CreateDatabase(string $driver, string $host, ?string $username, ?string $password, ?string $database) : bool {
        $dbp = $this->GetDatabaseProvider(new DatabaseConfigurationProfile('', $driver, $host, $username, $password));
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