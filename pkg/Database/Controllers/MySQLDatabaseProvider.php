<?php

namespace Package\Database\Controllers;

use PDO;
use PDOException;

class MySQLDatabaseProvider extends PDO implements IDatabaseProvider
{
    private string $host;
    private string $database;
    private string $username;
    private string $password;

    private string $connectionStatus;

    public function __construct(string $host, string $database, string $username, string $password) {
        $this->host = $host;
        $this->database = $database;
        $this->username = $username;
        $this->password = $password;

        
    }

    public function OpenConnection() : bool
    {
        try {
            parent::__construct(
                "mysql:host=" . $this->host . ";dbname=" . $this->database,
                $this->username,
                $this->password,
            );
            $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            $this->connectionStatus = $e->getMessage();
            return false;
        }

        return true;
    }
}