<?php

namespace Package\Database\Controllers;

use Package\Database\Extensions\Where;
use Package\Database\Models\Type;

interface IDatabaseProvider
{
    // Methods for opening/checking host/user/connection
    public function OpenConnection() : bool;

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
    public function InsertOrUpdate(string $tableName, array $data, string $primaryKeyName) : void;
    public function Delete(string $tableName, Where $where, $order, $limit) : void;

    // Helper methods
    public static function ConvertToSQLType(string $phpType) : Type;
}