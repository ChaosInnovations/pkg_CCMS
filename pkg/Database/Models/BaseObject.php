<?php

namespace Package\Database\Models;

use Package\Database\Controllers\IDatabaseProvider;
use Package\Database\Extensions\TableName;
use Package\Database\Services\DatabaseService;
use ReflectionClass;

class BaseObject
{
    protected static null|Table $table = null;

    protected static function getTable() : Table {
        if (!self::$table instanceof Table) {
            self::$table  = new Table(self::getDbi(), self::getTableName());
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

    protected function UpdateOrCreateEntry() : bool {
        return false;
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