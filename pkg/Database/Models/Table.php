<?php

namespace Package\Database\Models;

use Package\Database\Controllers\IDatabaseProvider;
use Package\Database\Services\DatabaseService;

class Table
{
    public IDatabaseProvider $dbp;
    public string $tableName;

    private $serverColumns;
    /** @var TableColumn[] */
    private array $columns;
    /** @return TableColumn[] */
    public function GetColumns() : array { return $this->columns; }

    public function __construct(IDatabaseProvider $dbp, string $tableName, array $columns)
    {
        $this->dbp = $dbp;
        $this->tableName = $tableName;
        $this->columns = $columns;
    }

    public function Exists() : bool {
        // should also categorize whether this is actually a table, view, or information_schema
        return $this->dbp->TableExists($this->tableName);
    }
    public function GetPrimaryKeyColumn() : null|TableColumn {
        foreach ($this->columns as $column) {
            if ($column->primaryKey) {
                return $column;
            }
        }

        return null;
    }
}