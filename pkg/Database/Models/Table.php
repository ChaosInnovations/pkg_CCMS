<?php

namespace Package\Database\Models;

use Package\Database\Controllers\IDatabaseProvider;
use Package\Database\Services\DatabaseService;

class Table
{
    public IDatabaseProvider $dbp;
    public string $tableName;

    public function __construct(IDatabaseProvider $dbp, string $tableName)
    {
        $this->dbp = $dbp;
        $this->tableName = $tableName;
    }

    public function Exists() : bool {
        // should also categorize whether this is actually a table, view, or information_schema
        return $this->dbp->TableExists($this->tableName);
    }
}