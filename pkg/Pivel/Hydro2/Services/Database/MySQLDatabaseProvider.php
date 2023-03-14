<?php

namespace Package\Pivel\Hydro2\Services\Database;

use Package\Pivel\Hydro2\Database\Extensions\Exceptions\HostNotFoundException;
use Package\Pivel\Hydro2\Database\Extensions\Exceptions\InvalidUserException;
use Package\Pivel\Hydro2\Database\Extensions\Exceptions\TableNotFoundException;
use Package\Pivel\Hydro2\Database\Extensions\OrderBy;
use Package\Pivel\Hydro2\Database\Extensions\Where;
use Package\Pivel\Hydro2\Database\Models\DatabaseConfigurationProfile;
use Package\Pivel\Hydro2\Database\Models\TableColumn;
use Package\Pivel\Hydro2\Database\Models\Type;
use PDO;
use PDOException;

class MySQLDatabaseProvider extends PDO implements IDatabaseProvider
{
    private string $host;
    private ?string $database;
    private ?string $username;
    private ?string $password;

    public function __construct(DatabaseConfigurationProfile $profile) {
        $this->host = $profile->Host;
        $this->database = $profile->DatabaseSchema;
        $this->username = $profile->Username;
        $this->password = $profile->Password;

        
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
        },array_filter($columns,fn(TableColumn $col)=>($col->primaryKey||$col->foreignKey)&&self::getConstraintSQL($col)!==null)));

        if ($constraintStructureString != '') {
            $columnStructureString .= ','.$constraintStructureString;
        }

        $stmt = $this->prepare("CREATE TABLE IF NOT EXISTS ".$tableName." (".$columnStructureString.")");
        $stmt->execute();
    }

    public function Select(string $tableName, array $columns, ?Where $where, ?OrderBy $order, ?int $limit, ?int $offset) : array {
        $columnsString = implode(',',array_map(fn($col):string=>$col->columnName,$columns));
        $query = 'SELECT '.$columnsString.' FROM '.$tableName;
        $query .= ($where==null?'':' '.$where->GetParameterizedQueryString());
        $query .= ($order==null?'':' '.$order->GetQueryString());
        $query .= ($limit==null?'':' LIMIT '.($offset==null?'':''.$offset.', ').$limit);
        try {
            $stmt = $this->prepare($query);
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

    public function Insert(string $tableName, array $data) : int {
        $columnsString = implode(',', array_keys($data));
        $valuePlaceholdersString = implode(',', array_map(fn($v):string=>':'.$v,array_keys($data)));
        try {
            $stmt = $this->prepare("INSERT INTO ".$tableName." (".$columnsString.") VALUES (".$valuePlaceholdersString.")");
            $stmt->execute($data);
        } catch (PDOException $e) {
            if ($e->errorInfo[0] == '42S02') {
                throw new TableNotFoundException($e->getMessage(), 0);
            } else {
                throw $e;
            }
        }

        return intval(parent::lastInsertId());
    }

    public function InsertOrUpdate(string $tableName, array $data, ?string $primaryKeyName) : int {
        $columnsString = implode(',', array_keys($data));
        $valuePlaceholdersString = implode(',', array_map(fn($k):string=>':'.$k,array_keys($data)));
        $updateValuesPlaceholderString = implode(',', array_map(fn($k):string=>$k.'=:'.$k,array_filter(array_keys($data),fn($k)=>$k!=$primaryKeyName)));
        try {
            $stmt = $this->prepare("INSERT INTO ".$tableName." (".$columnsString.") VALUES (".$valuePlaceholdersString.") ON DUPLICATE KEY UPDATE ".$updateValuesPlaceholderString);
            $stmt->execute($data);
        } catch (PDOException $e) {
            if ($e->errorInfo[0] == '42S02') {
                throw new TableNotFoundException($e->getMessage(), 0);
            } else {
                throw $e;
            }
        }

        return intval(parent::lastInsertId());
    }

    public function Delete(string $tableName, Where $where, $order, $limit) : void {
        try {
            $stmt = $this->prepare("DELETE FROM ".$tableName.($where==null?"":" ".$where->GetParameterizedQueryString()));
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
        $s = $col->columnName.' '.$col->columnType->value.($col->autoIncrement?' AUTOINCREMENT':'');
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