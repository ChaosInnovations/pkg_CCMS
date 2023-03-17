<?php

namespace Pivel\Hydro2\Services\Database;

use Pivel\Hydro2\Extensions\Database\OrderBy;
use Pivel\Hydro2\Extensions\Database\Where;
use Pivel\Hydro2\Models\Database\DatabaseConfigurationProfile;
use Pivel\Hydro2\Models\Database\Type;

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
    public function Select(string $tableName, array $columns, ?Where $where, ?OrderBy $order, ?int $limit, ?int $offset) : array;
    public function Insert(string $tableName, array $data) : int;
    //public function Update(string $tableName, $data, $columns, $where, $order, $limit);
    /** @return int The ID of inserted/updated row. */
    public function InsertOrUpdate(string $tableName, array $data, ?string $primaryKeyName) : int;
    public function Delete(string $tableName, Where $where, $order, $limit) : void;

    // Helper methods
    public static function ConvertToSQLType(string $phpType) : Type;
}