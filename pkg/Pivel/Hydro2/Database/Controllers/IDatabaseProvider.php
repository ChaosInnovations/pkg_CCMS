<?php

namespace Package\Pivel\Hydro2\Database\Controllers;

use Package\Pivel\Hydro2\Database\Extensions\Where;
use Package\Pivel\Hydro2\Database\Models\DatabaseConfigurationProfile;
use Package\Pivel\Hydro2\Database\Models\Type;

interface IDatabaseProvider
{
    public function __construct(DatabaseConfigurationProfile $profile);
    // Methods for opening/checking host/user/connection
    public function OpenConnection() : bool;
    public function CanCreateDatabases(?string $username=null) : bool;
    /** @return string[] */
    public function GetDatabases() : array;
    public function CreateDatabase(string $database) : bool;

    // Methods for manipulating tables
    public function TableExists(string $tableName) : bool;
    public function CreateTableIfNotExists(string $tableName, array $columns);
    //public function DropTable(string $tableName);
    //public function AddTableColumn(string $tableName, $column);
    //public function DropTableColumn(string $tableName, $column);
    //public function AlterTableColumn(string $tableName, $oldColumn, $newColumn);
    //public function ReorderColumn(string $tableName, $column, $after=null);

    // Methods for manipulating table data
    public function Select(string $tableName, array $columns, null|Where $where, $order, $limit) : array;
    public function Insert(string $tableName, array $data) : void;
    //public function Update(string $tableName, $data, $columns, $where, $order, $limit);
    public function InsertOrUpdate(string $tableName, array $data, ?string $primaryKeyName) : void;
    public function Delete(string $tableName, Where $where, $order, $limit) : void;

    // Helper methods
    public static function ConvertToSQLType(string $phpType) : Type;
}