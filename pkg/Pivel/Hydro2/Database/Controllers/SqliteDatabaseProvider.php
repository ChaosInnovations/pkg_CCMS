<?php

namespace Package\Pivel\Hydro2\Database\Controllers;

use Package\Pivel\Hydro2\Database\Extensions\Exceptions\TableNotFoundException;
use Package\Pivel\Hydro2\Database\Extensions\OrderBy;
use Package\Pivel\Hydro2\Database\Extensions\Where;
use Package\Pivel\Hydro2\Database\Models\DatabaseConfigurationProfile;
use Package\Pivel\Hydro2\Database\Models\TableColumn;
use Package\Pivel\Hydro2\Database\Models\Type;
use PDO;
use PDOException;

class SqliteDatabaseProvider extends PDO implements IDatabaseProvider
{
    private string $file;

    public function __construct(DatabaseConfigurationProfile $profile) {
        $this->file = $profile->Host;
    }

    public function OpenConnection() : bool
    {
        try {
            parent::__construct(
                "sqlite:" . $this->file
            );
            $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Sqlite disables foreign keys by default; they must be enabled in each session.
            $stmt = $this->prepare("PRAGMA foreign_keys = ON;");
            $stmt->execute();
        } catch(PDOException $e) {
            //if ($e->errorInfo[0] == 'HY000' && $e->errorInfo[1] == 2002) {
            //    throw new HostNotFoundException($e->getMessage(), 0);
            //}
            //if ($e->errorInfo[0] == 'HY000' && $e->errorInfo[1] == 1045) {
            //    throw new InvalidUserException($e->getMessage(), 0);
            //}
            return false;
        }

        return true;
    }

    public function CanCreateDatabases(?string $username=null) : bool {
        // Sqlite doesn't have multiple databases in a file
        return true;
    }

    public function GetDatabases() : array {
        // Sqlite doesn't have multiple databases in a file
        return [];
    }

    public function CreateDatabase(string $database) : bool {
        // Sqlite doesn't have multiple databases in a file
        return true;
    }

    public function TableExists(string $tableName) : bool
    {
        $stmt = $this->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=':tblname'");
        $stmt->execute(['tblname'=>$tableName]);
        $res = $stmt->fetchAll();
        return count($res) == 1;
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
            if ($e->errorInfo[0] == 'HY000' && $e->errorInfo[1] == 1 && str_contains($e->errorInfo[2], 'no such table')) {
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
            if ($e->errorInfo[0] == 'HY000' && $e->errorInfo[1] == 1 && str_contains($e->errorInfo[2], 'no such table')) {
                throw new TableNotFoundException($e->getMessage(), 0);
            } else {
                throw $e;
            }
        }

        return intval(parent::lastInsertId());
    }

    public function InsertOrUpdate(string $tableName, array $data, ?string $primaryKeyName) : int {
        $columnsString = implode(',', array_map(fn($k):string=>'`'.$k.'`',array_keys($data)));
        $valuePlaceholdersString = implode(',', array_map(fn($k):string=>':'.$k,array_keys($data)));
        $updateValuesPlaceholderString = implode(',', array_map(fn($k):string=>'`'.$k.'`=:'.$k,array_filter(array_keys($data),fn($k)=>$k!=$primaryKeyName)));
        try {
            $stmt = $this->prepare("INSERT OR IGNORE INTO ".$tableName." (".$columnsString.") VALUES (".$valuePlaceholdersString.")");
            $stmt->execute($data);
            if ($primaryKeyName !== null) {
                $stmt = $this->prepare("UPDATE ".$tableName." SET ".$updateValuesPlaceholderString." WHERE `".$primaryKeyName."`=:".$primaryKeyName);
                $stmt->execute($data);
            }
        } catch (PDOException $e) {
            if ($e->errorInfo[0] == 'HY000' && $e->errorInfo[1] == 1 && str_contains($e->errorInfo[2], 'no such table')) {
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
            if ($e->errorInfo[0] == 'HY000' && $e->errorInfo[1] == 1 && str_contains($e->errorInfo[2], 'no such table')) {
                throw new TableNotFoundException($e->getMessage(), 0);
            } else {
                throw $e;
            }
        }
    }

    static private function getColumnSQL(TableColumn $col) : string {
        // column_name [def] [PRIMARY KEY|FOREIGN KEY]
        $s = $col->columnName.' '.self::getEquivalentType($col->columnType).(($col->autoIncrement&&$col->primaryKey)?' PRIMARY KEY AUTOINCREMENT':'');
        return $s;
    }

    static private function getConstraintSQL(TableColumn $col) : null|string {
        // column_name [def] [PRIMARY KEY|FOREIGN KEY]
        if ($col->primaryKey) {
            return null; //sqlite primary keys are declared inline.
        }

        if ($col->foreignKey) {
            $s = 'FOREIGN KEY ('.$col->columnName.') REFERENCES '.$col->foreignKeyTable.'('.$col->foreignKeyColumnName.')';
            $s .= ' ON UPDATE '.$col->foreignKeyOnUpdate->value.' ON DELETE '.$col->foreignKeyOnDelete->value;
            return $s;
        }

        return null;
    }

    public static function getEquivalentType(Type $type) : string {
        if ($type == Type::INT) {
            return "INTEGER";
        }

        return $type->value;
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
        return Type::TEXT;
    }
}