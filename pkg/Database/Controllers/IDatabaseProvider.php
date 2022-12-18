<?php

namespace Package\Database\Controllers;

interface IDatabaseProvider
{
    public function OpenConnection() : bool;
    public function TableExists(string $tableName) : bool;

    // Helper methods
    public static function ConvertToSQLType(string $phpType) : Type;
}