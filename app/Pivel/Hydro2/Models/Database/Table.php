<?php

namespace Pivel\Hydro2\Models\Database;

use Pivel\Hydro2\Exceptions\Database\TableNotFoundException;
use Pivel\Hydro2\Extensions\Database\OrderBy;
use Pivel\Hydro2\Extensions\Database\Where;
use Pivel\Hydro2\Services\Database\IDatabaseProvider;

class Table
{
    public ?IDatabaseProvider $dbp;
    public function IsConnected() : bool { return $this->dbp !== null; }
    public string $tableName;

    private $serverColumns;
    /** @var TableColumn[] */
    private array $columns;
    /** @return TableColumn[] */
    public function GetColumns() : array { return $this->columns; }

    public function __construct(?IDatabaseProvider $dbp, string $tableName, array $columns)
    {
        $this->dbp = $dbp;
        $this->tableName = $tableName;
        $this->columns = $columns;
        

        // Need to compare local column structure and server column structure.
        // If there is a mis-match, need to create table or alter/add table columns to reconcile
        // differences could be:
        // Server is missing column(s)
        // Server has extra column(s)
        // Columns out of order
        // Column(s) have wrong type or other attributes
        // Table-level attributes don't match (collation, storage engine, partition)
        
        // There would be a performance impact if the above is checked on
        //  every request and every time an object's Table is instantiated.
        //  This should only be checked if necessary:
        //   - Operate using just the local column representation unless an error is
        //     encountered that would suggest there is a structure mis-match. We
        //     should try to automatically reconcile (only in cases where we need to
        //     create a new table or add a new column), otherwise throw an error
        //     (the calling controller should return a 500 response) and save a log
        //     entry with details. We should not delete or alter column structures
        //     automatically due to the risk of unintentional data loss.
    }

    public function Exists() : bool {
        // should also categorize whether this is actually a table, view, or information_schema
        if (!$this->IsConnected()) {
            return false;
        }
        return $this->dbp->TableExists($this->tableName);
    }

    public function HasColumn(string $columnName) : bool {
        return false;
    }

    public function GetPrimaryKeyColumn() : null|TableColumn {
        foreach ($this->columns as $column) {
            if ($column->primaryKey) {
                return $column;
            }
        }

        return null;
    }

    public function CreateTable() : bool {
        if (!$this->IsConnected()) {
            return false;
        }

        $this->dbp->CreateTableIfNotExists($this->tableName, $this->columns);
        return true;
    }

    /**
     * @param ?TableColumn[] $columns
     */
    public function Select(?array $columns=null, ?Where $where=null, ?OrderBy $order=null, ?int $limit=null, ?int $offset=null) {
        if (!$this->IsConnected()) {
            return [];
        }

        if ($columns == null) {
            $columns = $this->columns;
        }

        try {
            $results = $this->dbp->Select($this->tableName, $columns, $where, $order, $limit, $offset);
        } catch (TableNotFoundException) {
            if (!$this->CreateTable()) {
                return [];
            }
            $results = $this->dbp->Select($this->tableName, $columns, $where, $order, $limit, $offset);
        }
        
        return $results;
    }
    
    public function Insert(array $data) : bool|int {
        /*[
            'column_name' => value,
            'column_name2' => value2,
        ]*/
        if (!$this->IsConnected()) {
            return false;
        }

        foreach ($data as $key => $value) {
            if (is_subclass_of($this->columns[$key]->propertyType, BaseObject::class)) {
                $data[$key] = $value->GetPrimaryKeyColumn();
            } else if ($this->columns[$key]->propertyType == 'DateTime') {
                $data[$key] = $value->format('c');
            }
        }

        try {
            $rowId = $this->dbp->Insert($this->tableName, $data);
        } catch (TableNotFoundException) {
            if (!$this->CreateTable()) {
                return false;
            }
            $rowId = $this->dbp->Insert($this->tableName, $data);
        }

        return $rowId;
    }
    
    //public function Update($data, $where, $order, $limit);
    
    public function InsertOrUpdate($data) : bool|int {
        if (!$this->IsConnected()) {
            return false;
        }

        foreach ($data as $key => $value) {
            if (is_subclass_of($this->columns[$key]->propertyType, BaseObject::class)) {
                $data[$key] = $value->GetPrimaryKeyValue();
            } else if ($this->columns[$key]->propertyType == 'DateTime') {
                $data[$key] = $value->format('c');
            }
        }

        try {
            $rowId = $this->dbp->InsertOrUpdate($this->tableName, $data, $this->GetPrimaryKeyColumn()->columnName);
        } catch (TableNotFoundException) {
            if (!$this->CreateTable()) {
                return false;
            }
            $rowId = $this->dbp->InsertOrUpdate($this->tableName, $data, $this->GetPrimaryKeyColumn()->columnName);
        }

        return $rowId;
    }

    public function DeleteId(mixed $id) : bool {
        return $this->Delete((new Where())->Equal($this->GetPrimaryKeyColumn()->columnName, $id), null, null);
    }
    
    public function Delete(Where $where, $order, $limit) : bool {
        if (!$this->IsConnected()) {
            return false;
        }

        try {
            $this->dbp->Delete($this->tableName, $where, $order, $limit);
        } catch (TableNotFoundException) {
            if (!$this->CreateTable()) {
                return false;
            }
            $this->dbp->Delete($this->tableName, $where, $order, $limit);
        }

        return true;
    }
}