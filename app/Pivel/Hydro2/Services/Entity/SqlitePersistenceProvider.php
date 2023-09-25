<?php

namespace Pivel\Hydro2\Services\Entity;

use PDO;
use PDOException;
use Pivel\Hydro2\Exceptions\Database\TableNotFoundException;
use Pivel\Hydro2\Extensions\Query;
use Pivel\Hydro2\Models\Database\Type;
use Pivel\Hydro2\Models\EntityDefinition;
use Pivel\Hydro2\Models\EntityFieldDefinition;
use Pivel\Hydro2\Models\EntityPersistenceProfile;

class SqlitePersistenceProvider implements IEntityPersistenceProvider
{
    public static function GetFriendlyName() : string
    {
        return 'Sqlite';
    }

    private string $file;
    private bool $connected;
    private ?PDO $pdo;

    public function __construct(EntityPersistenceProfile $profile)
    {
        $this->file = $profile->GetHostOrPath();
        $this->connected = false;
        $this->pdo = null;
    }

    public function __destruct()
    {
        $this->CloseConnection();
    }

    private function OpenConnection() : bool
    {
        if ($this->connected) {
            return true; // Already connected.
        }

        try {
            $this->pdo = new PDO(
                "sqlite:" . $this->file
            );
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Sqlite disables foreign keys by default; they must be enabled in each session.
            $stmt = $this->pdo->prepare("PRAGMA foreign_keys = ON;");
            $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }

        $this->connected = true;
        return true;
    }

    private function CloseConnection() : void
    {
        $this->connected = false;
        $this->pdo = null;
    }

    // Profile validation
    public function IsProfileValid() : bool
    {
        return $this->OpenConnection();
    }

    // Schema manipulation
    public function CanCreateDatabaseSchemas() : bool
    {
        // Sqlite doesn't have multiple schemas.
        return false;
    }

    public function GetDatabaseSchemas() : array {
        // Sqlite doesn't have multiple schemas.
        return [];
    }

    public function CreateDatabaseSchema(string $schemaName) : bool
    {
        // Sqlite doesn't have multiple schemas.
        return false;
    }

    // Collection manipulation
    public function CollectionExists(EntityDefinition $collection) : bool
    {
        if (!$this->OpenConnection()) {
            return false;
        }

        $stmt = $this->pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=':tblname'");
        $stmt->execute(['tblname'=>$collection->GetName()]);
        $res = $stmt->fetchAll();
        return count($res) == 1;
    }

    public function CreateCollectionIfNotExists(EntityDefinition $collection) : bool
    {
        if (!$this->OpenConnection()) {
            return false;
        }

        $columnStructureString = implode(',',array_map(function(EntityFieldDefinition $field) {
            return self::getColumnSQL($field);
        },$collection->GetFields()));

        $constraintStructureString = implode(',',array_map(function($field) {
            return self::getConstraintSQL($field);
        },array_filter($collection->GetFields(),fn(EntityFieldDefinition $field)=>($field->IsPrimaryKey||$field->IsForeignKey)&&self::getConstraintSQL($field)!==null)));

        if ($constraintStructureString != '') {
            $columnStructureString .= ','.$constraintStructureString;
        }

        $stmt = $this->pdo->prepare("CREATE TABLE IF NOT EXISTS {$collection->GetName()} ({$columnStructureString})");
        $stmt->execute();

        return true;
    }

    // Entity/data manipulation
    public function Select(EntityDefinition $collection, ?Query $query) : array
    {
        if (!$this->OpenConnection()) {
            return [];
        }

        $columnsString = implode(',',array_map(fn(EntityFieldDefinition $field):string=>$field->FieldName,$collection->GetFields()));
        $queryString = 'SELECT '.$columnsString.' FROM '.$collection->GetName();
        if ($query !== null) {
            $queryString .= self::GetWhereStringFromQuery($query);
            $queryString .= self::GetOrderStringFromQuery($query);
            $queryString .= (($query->GetLimit()<=-1&&$query->GetOffset()==0)?'':' LIMIT '.($query->GetOffset()==0?'':''.$query->GetOffset().', ').$query->GetLimit());
        }
        try {
            $stmt = $this->pdo->prepare($queryString);
            $stmt->execute($query==null?[]:$query->GetFilterParameters());
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            if ($e->errorInfo[0] == 'HY000' && $e->errorInfo[1] == 1 && str_contains($e->errorInfo[2], 'no such table')) {
                throw new TableNotFoundException($e->getMessage(), 0);
            } else {
                throw $e;
            }
        }
    }

    public function Count(EntityDefinition $collection, ?Query $query) : int
    {
        if (!$this->OpenConnection()) {
            return 0;
        }
        
        $queryString = 'SELECT COUNT(*) FROM '.$collection->GetName();
        $queryString .= self::GetWhereStringFromQuery($query);
        try {
            $stmt = $this->pdo->prepare($queryString);
            $stmt->execute($query==null?[]:$query->GetFilterParameters());
            return $stmt->fetchAll()[0][0];
        } catch (PDOException $e) {
            if ($e->errorInfo[0] == 'HY000' && $e->errorInfo[1] == 1 && str_contains($e->errorInfo[2], 'no such table')) {
                throw new TableNotFoundException($e->getMessage(), 0);
            } else {
                throw $e;
            }
        }
    }

    public function Insert(EntityDefinition $collection, array $fieldValues) : ?int
    {
        if (!$this->OpenConnection()) {
            return null;
        }

        $columnsString = implode(',',array_map(fn(EntityFieldDefinition $field):string=>$field->FieldName,$collection->GetFields()));
        $valuePlaceholdersString = implode(',', array_map(fn($v):string=>':'.$v,array_keys($fieldValues)));
        try {
            $stmt = $this->pdo->prepare("INSERT INTO ".$collection->GetName()." (".$columnsString.") VALUES (".$valuePlaceholdersString.")");
            $stmt->execute($fieldValues);
        } catch (PDOException $e) {
            if ($e->errorInfo[0] == 'HY000' && $e->errorInfo[1] == 1 && str_contains($e->errorInfo[2], 'no such table')) {
                throw new TableNotFoundException($e->getMessage(), 0);
            } else {
                throw $e;
            }
        }

        if ($collection->GetPrimaryKeyField() == null) {
            return null;
        }

        return intval($this->pdo->lastInsertId());
    }

    public function InsertOrUpdate(EntityDefinition $collection, array $fieldValues) : ?int
    {
        if (!$this->OpenConnection()) {
            return null;
        }

        $columnsString = implode(',',array_map(fn(EntityFieldDefinition $field):string=>$field->FieldName,$collection->GetFields()));
        $valuePlaceholdersString = implode(',', array_map(fn($k):string=>':'.$k,array_keys($fieldValues)));
        $pkField = $collection->GetPrimaryKeyField();
        $pkFieldName = $pkField == null ? null : $pkField->FieldName;
        $updateValuesPlaceholderString = implode(',', array_map(fn($k):string=>'`'.$k.'`=:'.$k,array_filter(array_keys($fieldValues),fn($k)=>$k!=$pkFieldName)));
        try {
            $stmt = $this->pdo->prepare("INSERT OR IGNORE INTO ".$collection->GetName()." (".$columnsString.") VALUES (".$valuePlaceholdersString.")");
            $stmt->execute($fieldValues);
            if ($pkFieldName !== null) {
                $stmt = $this->pdo->prepare("UPDATE ".$collection->GetName()." SET ".$updateValuesPlaceholderString." WHERE `".$pkFieldName."`=:".$pkFieldName);
                $stmt->execute($fieldValues);
            }
        } catch (PDOException $e) {
            if ($e->errorInfo[0] == 'HY000' && $e->errorInfo[1] == 1 && str_contains($e->errorInfo[2], 'no such table')) {
                throw new TableNotFoundException($e->getMessage(), 0);
            } else {
                throw $e;
            }
        }

        if ($pkField == null) {
            return null;
        }

        $lastInsertId = $this->pdo->lastInsertId();
        if ($lastInsertId == 0) {
            return null;
        }
        return $lastInsertId ? null : intval($lastInsertId);
    }

    public function Delete(EntityDefinition $collection, Query $query) : int
    {
        if (!$this->OpenConnection()) {
            return 0;
        }

        try {
            $stmt = $this->pdo->prepare("DELETE FROM " . $collection->GetName() . self::GetWhereStringFromQuery($query));
            $stmt->execute($query->GetFilterParameters());
        } catch (PDOException $e) {
            if ($e->errorInfo[0] == 'HY000' && $e->errorInfo[1] == 1 && str_contains($e->errorInfo[2], 'no such table')) {
                throw new TableNotFoundException($e->getMessage(), 0);
            } else {
                throw $e;
            }
        }

        return $stmt->rowCount();
    }

    // Helpers
    private static function getColumnSQL(EntityFieldDefinition $field) : string {
        // column_name [def] [PRIMARY KEY|FOREIGN KEY]
        $s = $field->FieldName.' '.self::getEquivalentType($field->FieldType).(($field->AutoIncrement&&$field->IsPrimaryKey)?' PRIMARY KEY AUTOINCREMENT':'');
        return $s;
    }

    private static function getConstraintSQL(EntityFieldDefinition $field) : null|string {
        // column_name [def] [PRIMARY KEY|FOREIGN KEY]
        if ($field->IsPrimaryKey) {
            return null; //sqlite primary keys are declared inline.
        }

        if ($field->IsForeignKey) {
            $s = 'FOREIGN KEY ('.$field->FieldName.') REFERENCES '.$field->ForeignKeyCollectionName.'('.$field->foreignKeyCollectionFieldName.')';
            $s .= ' ON UPDATE '.$field->ForeignKeyOnUpdate->value.' ON DELETE '.$field->ForeignKeyOnDelete->value;
            return $s;
        }

        return null;
    }

    private static function getEquivalentType(Type $type) : string {
        if ($type == Type::INT) {
            return "INTEGER";
        }

        return $type->value;
    }

    //self::GetWhereStringFromQuery($query); // ($query->GetFilterTree()==null?'':' '.$where->GetParameterizedQueryString());
    private static function GetWhereStringFromQuery(?Query $query) : string
    {
        if ($query === null) {
            return '';
        }

        $filterTree = $query->GetFilterTree();

        if (count($filterTree['operands']) == 0) {
            return '';
        }

        return ' WHERE ' . self::GetWhereStringPortionFromFilterTree($filterTree);
    }

    private static function GetWhereStringPortionFromFilterTree(array $filterTree) : string
    {
        if (isset($filterTree['operator'])) {
            // this is a condition, not a group.
            return ($filterTree['negated'] ? 'NOT ' : '') . $filterTree['field'] . ' ' . $filterTree['operator'] . ' :' . $filterTree['parameterKey'];
        }

        $queryString = implode(' ' . $filterTree['booloperator'] . ' ', array_map(function($operand){
            return self::GetWhereStringPortionFromFilterTree($operand);
        }, $filterTree['operands']));

        if (count($filterTree['operands']) > 1) {
            $queryString = '(' . $queryString . ')';
        }

        return $queryString;
    }

    private static function GetOrderStringFromQuery(?Query $query) : string
    {
        if ($query === null) {
            return '';
        }

        $orderTree = $query->GetOrderTree();
        if (count($orderTree) == 0) {
            return '';
        }

        return ' ORDER BY ' . implode(',', array_map(function($o){
            return $o['field'] . ' ' . $o['direction']->value;
        }, $orderTree));
    }
}