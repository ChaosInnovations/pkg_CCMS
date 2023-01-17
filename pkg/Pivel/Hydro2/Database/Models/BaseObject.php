<?php

namespace Package\Pivel\Hydro2\Database\Models;

use DateTime;
use Package\Pivel\Hydro2\Database\Controllers\IDatabaseProvider;
use Package\Pivel\Hydro2\Database\Extensions\TableColumn;
use Package\Pivel\Hydro2\Database\Extensions\TableForeignKey;
use Package\Pivel\Hydro2\Database\Extensions\TableName;
use Package\Pivel\Hydro2\Database\Extensions\TablePrimaryKey;
use Package\Pivel\Hydro2\Database\Extensions\Where;
use Package\Pivel\Hydro2\Database\Models\TableColumn as ModelsTableColumn;
use Package\Pivel\Hydro2\Database\Services\DatabaseService;
use ReflectionClass;

abstract class BaseObject
{
    /**
     * Needs to be an array because static properties in a base class are shared between child/siblings
     * 
     * @var Table[]
     */
    protected static array $tables = [];

    protected static function getTable() : ?Table {
        if (!isset(self::$tables[get_called_class()]) || !self::$tables[get_called_class()] instanceof Table) {
            self::$tables[get_called_class()] = new Table(self::getDbi(), self::getTableName(), self::getColumns());
        }

        return self::$tables[get_called_class()];
    }

    public function __construct()
    {
        
    }

    private static function getDbi() : ?IDatabaseProvider {
        if (!DatabaseService::IsPrimaryConnected()) {
            return null;
        }

        return DatabaseService::Instance()->databaseProvider;
    }

    private static function getTableName() : string {
        // get name from child class' DBObject attribute. ->tableName
        // if attribute doesn't exist, or maybe we should just do this anyways: generate a name based on the
        //  child class' full namespaced name.
        //  i.e. Package\Database\Models\TestObject -> database_testobject
        //  i.e. Package\Vendor\Database\Models\TestObject -> vendor_database_testobject
        //       or -> t_[md5(::class)] since table names have a max length of 64 characters.
        //  -> how to handle migrations/future versions where class name changes?
        //  --> in the existing migration methods, such a class could just manually run the queries to change the
        //       table name.

        // should probably have the option to set a name manually via the [#TableName] attribute
        $name = 't_' . md5(get_called_class());

        $class = new ReflectionClass(get_called_class());
        $class_attributes = $class->getAttributes(TableName::class);
        if (count($class_attributes) == 1) {
            /** @var TableName */
            $tableName = $class_attributes[0]->newInstance();
            $name = $tableName->tableName;
        }

        return $name;
    }

    private static function getColumns() : array {
        // test table structure
        $class = new ReflectionClass(get_called_class());

        $columns = [];

        foreach ($class->getProperties() as $property) {
            $method_attributes = $property->getAttributes(TableColumn::class);
            $method_pk_attributes = $property->getAttributes(TablePrimaryKey::class);
            $method_fk_attributes = $property->getAttributes(TableForeignKey::class);
            $isPrimaryKey = count($method_pk_attributes) == 1;
            
            if (count($method_attributes) != 1) {
                continue;
            }

            /** @var TableColumn */
            $tableColumn = $method_attributes[0]->newInstance();

            $column_name = $tableColumn->columnName;
            $ai = $tableColumn->autoIncrement;
            $sqlType = $tableColumn->sqlType;
            $phpType = $property->getType()->getName();
            $property_name = $property->getName();
            $foreignKey = false;
            $foreignKeyTable = null;
            $foreignKeyColumnName = null;
            $fkOnUpdate = ReferenceBehaviour::RESTRICT;
            $fkOnDelete = ReferenceBehaviour::RESTRICT;

            if ($sqlType === null) {
                if (is_subclass_of($phpType, self::class)) {
                    // sql type should be the type of the primary key in the other class
                    // also need a FOREIGN KEY constraint
                    /** @var Table */
                    $foreignTable = $phpType::getTable();
                    $foreignColumn = $foreignTable->GetPrimaryKeyColumn();
                    $sqlType = $foreignColumn->columnType;
                    $foreignKey = true;
                    $foreignKeyTable = $foreignTable->tableName;
                    $foreignKeyColumnName = $foreignColumn->columnName;
                } else if (DatabaseService::IsPrimaryConnected()) {
                    $sqlType = self::getDbi()->ConvertToSQLType($phpType);
                } else {
                    $sqlType = Type::TEXT;
                }
            }
            
            if (count($method_fk_attributes) == 1) {
                /** @var TableForeignKey */
                $fkAttr = $method_fk_attributes[0]->newInstance();
                $fkOnUpdate = $fkAttr->onUpdate;
                $fkOnDelete = $fkAttr->onDelete;
                $foreignKey = true;
                $foreignKeyTable = $fkAttr->foreignTableName??$foreignTable->tableName;
                $foreignKeyColumnName = $fkAttr->foreignTableColumnName??$foreignKeyColumnName;
            }

            $columns[$column_name] = new ModelsTableColumn(
                $column_name,
                $property_name,
                $sqlType,
                $phpType,
                $ai,
                $isPrimaryKey,
                $foreignKey,
                $foreignKeyTable,
                $foreignKeyColumnName,
                $fkOnUpdate,
                $fkOnDelete,
            );
            
            // if the property type is another BaseObject, then that column's data should be the other
            // BaseObject's id and the column should have a constraint/relation to the other BaseObject's
            // id column.
        }

        //print_r($columns);
        return $columns;
    }

    public static function CastFromRow(array $row, string $className=null) : mixed {
        // get columns
        $columns = self::getTable()->GetColumns();

        // instantiate blank child class
        $className??=get_called_class();
        $object = $className::Blank();
        
        foreach ($row as $columnName => $value) {

            if (!isset($columns[$columnName])) {
                continue;
            }

            $column = $columns[$columnName];

            $sqlType = $column->columnType;
            $phpType = $column->propertyType;
            $propertyName = $column->propertyName;

            $castValue = null;
            if ($value !== null && is_subclass_of($phpType, self::class)) {
                // How to deal with objects that have circular foreign keys?
                $castValue = $phpType::LoadFromId($value);
            } else if ($phpType == 'DateTime') {
                $castValue = new DateTime($value.'+00:00');
            } else {
                $castValue = $value;
            }

            $object->SetProperty($propertyName, $castValue);
        }

        return $object;
    }

    public static function LoadFromId(int $id) : mixed {
        // 1. need to run a query like:
        //     SELECT * FROM [tablename] WHERE [idcolumnname] = [id];
        $table = self::getTable();
        $primaryKeyColumn = $table->GetPrimaryKeyColumn();
        if ($primaryKeyColumn === null) {
            return null;
        }
        $results = $table->Select(null, (new Where())->Equal($primaryKeyColumn->columnName, $id));
        // 2. check that there is a single result
        if (count($results) != 1) {
            return null;
        }
        // 3. 'cast' result to an instance of User
        // 4. return instance
        return self::CastFromRow($results[0], className:get_called_class());
    }

    public static function GetAll() : array {
        // 1. need to run a query like:
        //     SELECT * FROM [tablename];
        $table = self::getTable();
        $results = $table->Select();
        // 3. 'cast' each result to an instance of TestObject
        // 4. return array of instances

        return array_map(fn($row)=>self::CastFromRow($row), $results);
    }

    public function SetProperty($propertyName, $value) {
        $this->$propertyName = $value;
    }

    public function GetPrimaryKeyValue() {
        $pkColumn = self::getTable()->GetPrimaryKeyColumn();
        if ($pkColumn === null) {
            return null;
        }
        return $this->{$pkColumn->propertyName};
    }

    protected function UpdateOrCreateEntry() : bool {
        $data = [];
        foreach (self::getTable()->GetColumns() as $column) {
            if ($this->{$column->propertyName} === null) {
                continue;
            }
            $data[$column->columnName] = $this->{$column->propertyName};
        }
        return self::getTable()->InsertOrUpdate($data);
    }

    protected function DeleteEntry() : bool {
        return self::getTable()->DeleteId($this->GetPrimaryKeyValue());
    }
    
    // functions for creating database table if it doesn't yet exist, and
    // verifying that the database table matches the schema of this object.
    // - for efficiency, maybe only if we encounter an error that suggests the table doesn't exist?
    protected function VerifyTable() : bool {
        return false;
    }

    protected function CreateTable() : bool {
        return false;
    }
    
    // functions for database table migrations between versions?
}