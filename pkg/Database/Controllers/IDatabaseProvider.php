<?php

namespace Package\Database\Controllers;

interface IDatabaseProvider
{
    public function OpenConnection() : bool;
    public function TableExists(string $tableName) : bool;
    public function CreateTableIfNotExists(string $tableName, array $columns);
    // Methods for manipulating table data
    public function Select(string $tableName, array $columns, null|Where $where, $order, $limit) : array;

    // Helper methods
    public static function ConvertToSQLType(string $phpType) : Type;
}