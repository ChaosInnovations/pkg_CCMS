<?php

namespace Package\Database\Controllers;

use Package\Database\Extensions\Exceptions\HostNotFoundException;
use Package\Database\Extensions\Exceptions\InvalidUserException;
use Package\Database\Extensions\Exceptions\TableNotFoundException;
use Package\Database\Extensions\Where;
use Package\Database\Models\TableColumn;
use Package\Database\Models\Type;
use PDO;
use PDOException;

class MySQLDatabaseProvider extends PDO implements IDatabaseProvider
{
    private string $host;
    private ?string $database;
    private ?string $username;
    private ?string $password;

    public function __construct(string $host, ?string $database, ?string $username, ?string $password) {
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
            if ($e->errorInfo[0] == 'HY000' && $e->errorInfo[1] == 2002) {
                throw new HostNotFoundException($e->getMessage(), 0);
            }
            if ($e->errorInfo[0] == 'HY000' && $e->errorInfo[1] == 1045) {
                throw new InvalidUserException($e->getMessage(), 0);
            }
            return false;
        }

        return true;
    }

    /** @return string[] */
    private function GetUserGrants(?string $username=null) : array {
        $stmt = $this->prepare('SHOW GRANTS FOR '.($username??'CURRENT_USER'));
        $stmt->execute();
        $grants = $stmt->fetchAll();
        return array_map(fn($g)=>$g[0],$grants);
    }

    public function CanCreateDatabases(?string $username=null) : bool {
        $grants = $this->GetUserGrants($username);
        foreach ($grants as $grant) {
            if (strpos($grant, "GRANT ALL PRIVILEGES ON *.*") === 0) {
                return true;
            }
        }
        return false;
    }

    public function GetDatabases() : array {
        $grants = $this->GetUserGrants();
        // check where user has all privileges
        $dbsWithPrivileges = [];
        foreach ($grants as $grant) {
            if (strpos($grant, "GRANT ALL PRIVILEGES ON") === 0) {
                $matches = [];
                if (!preg_match("/(?<= )(`.*`|\*)(?=\.\*)/", $grant, $matches)) {
                    continue;
                }
                $match = trim($matches[0], '`');
                if ($match == '*') {
                    $stmt = $this->prepare('SHOW DATABASES;');
                    $stmt->execute();
                    $dbs = $stmt->fetchAll();
                    return array_map(fn($d)=>$d['Database'],$dbs);
                }
                array_push($dbsWithPrivileges, $match);
            }
        }
        return $dbsWithPrivileges;
    }

    public function CreateDatabase(string $database) : bool {
        $stmt = $this->prepare("CREATE DATABASE IF NOT EXISTS {$database};");
        $stmt->execute();
        return true;
    }

    public function TableExists(string $tableName) : bool
    {
        $stmt = $this->prepare("SELECT(IF(EXISTS(SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :dbname AND TABLE_NAME = :tblname),1,0))");
        $stmt->execute(['dbname'=>$this->database,'tblname'=>$tableName]);
        $res = $stmt->fetchAll();
        return $res[0][0] == 1;
    }

    /** @param TableColumn[] $columns */
    public function CreateTableIfNotExists(string $tableName, array $columns) : void {
        $columnStructureString = implode(',',array_map(function(TableColumn $col) {
            return self::getColumnSQL($col);
        },$columns));

        $constraintStructureString = implode(',',array_map(function($col) {
            return self::getConstraintSQL($col);
        },array_filter($columns,fn(TableColumn $col)=>$col->primaryKey||$col->foreignKey)));

        if ($constraintStructureString != '') {
            $columnStructureString .= ','.$constraintStructureString;
        }

        $stmt = $this->prepare("CREATE TABLE IF NOT EXISTS ".$tableName." (".$columnStructureString.")");
        $stmt->execute();
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

    public function Insert(string $tableName, array $data) : void {
        $columnsString = implode(',', array_keys($data));
        $valuePlaceholdersString = implode(',', array_map(fn($v):string=>':'.$v,array_keys($data)));
        $stmt = $this->prepare("INSERT INTO ".$tableName." (".$columnsString.") VALUES (".$valuePlaceholdersString.")");
        try {
            $stmt->execute($data);
        } catch (PDOException $e) {
            if ($e->errorInfo[0] == '42S02') {
                throw new TableNotFoundException($e->getMessage(), 0);
            } else {
                throw $e;
            }
        }
    }

    public function InsertOrUpdate(string $tableName, $data, $primaryKeyName) : void {
        $columnsString = implode(',', array_keys($data));
        $valuePlaceholdersString = implode(',', array_map(fn($k):string=>':'.$k,array_keys($data)));
        $updateValuesPlaceholderString = implode(',', array_map(fn($k):string=>$k.'=:'.$k,array_filter(array_keys($data),fn($k)=>$k!=$primaryKeyName)));
        $stmt = $this->prepare("INSERT INTO ".$tableName." (".$columnsString.") VALUES (".$valuePlaceholdersString.") ON DUPLICATE KEY UPDATE ".$updateValuesPlaceholderString);
        try {
            $stmt->execute($data);
        } catch (PDOException $e) {
            if ($e->errorInfo[0] == '42S02') {
                throw new TableNotFoundException($e->getMessage(), 0);
            } else {
                throw $e;
            }
        }
    }

    public function Delete(string $tableName, Where $where, $order, $limit) : void {
        $stmt = $this->prepare("DELETE FROM ".$tableName.($where==null?"":" ".$where->GetParameterizedQueryString()));
        try {
            $stmt->execute($where==null?[]:$where->GetParameters());
        } catch (PDOException $e) {
            if ($e->errorInfo[0] == '42S02') {
                throw new TableNotFoundException($e->getMessage(), 0);
            } else {
                throw $e;
            }
        }
    }

    static private function getColumnSQL(TableColumn $col) : string {
        // column_name [def] [PRIMARY KEY|FOREIGN KEY]
        $s = $col->columnName.' '.$col->columnType->value;
        return $s;
    }

    static private function getConstraintSQL(TableColumn $col) : null|string {
        // column_name [def] [PRIMARY KEY|FOREIGN KEY]
        if ($col->primaryKey) {
            return 'PRIMARY KEY ('.$col->columnName.')';
        }

        if ($col->foreignKey) {
            $s = 'FOREIGN KEY ('.$col->columnName.') REFERENCES '.$col->foreignKeyTable.'.'.$col->foreignKeyColumnName;
            $s .= ' ON UPDATE '.$col->foreignKeyOnUpdate->value.' ON DELETE '.$col->foreignKeyOnDelete->value;
            return $s;
        }

        return null;
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