<?php

namespace Package\Database\Controllers;

use Package\Database\Extensions\TableNotFoundException;
use Package\Database\Extensions\Where;
use Package\Database\Models\TableColumn;
use Package\Database\Models\Type;
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

    public function TableExists(string $tableName) : bool
    {
        $stmt = $this->prepare("SELECT(IF(EXISTS(SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :dbname AND TABLE_NAME = :tblname),1,0))");
        $stmt->execute(['dbname'=>$this->database,'tblname'=>$tableName]);
        $res = $stmt->fetchAll();
        return $res[0][0] == 1;
    }


    public function Select(string $tableName, array $columns, null|Where $where, $order, $limit) : array {
        $columnsString = implode(',',array_map(fn($col):string=>$col->columnName,$columns));
        $stmt = $this->prepare("SELECT ".$columnsString." FROM ".$tableName.($where==null?"":" ".$where->GetParameterizedQueryString()));
        try {
            $stmt->execute($where==null?[]:$where->GetParameters());
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            if ($e->errorInfo[0] == '42S02') {
                throw new TableNotFoundException($e->getMessage(), 0);
            } else {
                throw $e;
            }
        }
        return [];
    }
    public static function ConvertToSQLType(string $phpType) : Type {
        $type = Type::TEXT;
        switch ($phpType) {
            case 'bool':
                $type = Type::BOOLEAN;
                break;
            case 'int':
                $type = Type::INT;
                break;
            case 'float':
                $type = Type::DOUBLE;
                break;
            case 'string':
                $type = Type::TEXT;
                break;
            case 'DateTime':
                $type = Type::DATETIME;
                break;
            default:
                echo $phpType;
        }
        return $type;
    }
}