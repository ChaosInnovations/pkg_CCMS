<?php

namespace Package\Database\Models;

use Package\Database\Controllers\IDatabaseProvider;
use Package\Database\Extensions\TableColumn;
use Package\Database\Extensions\TableForeignKey;
use Package\Database\Extensions\TableName;
use Package\Database\Extensions\TablePrimaryKey;
use Package\Database\Models\TableColumn as ModelsTableColumn;
use Package\Database\Services\DatabaseService;
use ReflectionClass;

class BaseObject
{
    protected static null|Table $table = null;

    protected static function getTable() : Table {
        if (!self::$table instanceof Table) {
            self::$table = new Table(self::getDbi(), self::getTableName(), self::getColumns());
        }

        return self::$table;
    }

    public function __construct()
    {
        
    }

    private static function getDbi() : IDatabaseProvider {
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

        // should probably have the option to set a name manually via the [#TableStructue] attribute
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
                    if (count($method_fk_attributes) == 1) {
                        /** @var TableForeignKey */
                        $fkAttr = $method_fk_attributes[0]->newInstance();
                        $fkOnUpdate = $fkAttr->onUpdate;
                        $fkOnDelete = $fkAttr->onDelete;
                    }
                } else {
                    $sqlType = self::getDbi()->ConvertToSQLType($phpType);
                }
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
    }

    protected function LoadEntry() : bool {
        return false;
    }

    protected function DeleteEntry() : bool {
        return false;
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