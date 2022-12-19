<?php

namespace Package\Database\Models;

use Package\Database\Controllers\IDatabaseProvider;
use Package\Database\Extensions\TableNotFoundException;
use Package\Database\Extensions\Where;

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

    public function CreateTable() {
        $this->dbp->CreateTableIfNotExists($this->tableName, $this->columns);
    }

    public function Select(array $columns=null, $where=null, $order=null, $limit=null) {
        if ($columns == null) {
            $columns = $this->columns;
        }

        try {
            $results = $this->dbp->Select($this->tableName, $columns, $where, $order, $limit);
        } catch (TableNotFoundException) {
            $this->CreateTable();
            $results = $this->dbp->Select($this->tableName, $columns, $where, $order, $limit);
        }
        
        return $results;
    }
    
    public function Insert(array $data) {
        /*[
            'column_name' => value,
            'column_name2' => value2,
        ]*/

        foreach ($data as $key => $value) {
            if (is_subclass_of($this->columns[$key]->propertyType, BaseObject::class)) {
                $data[$key] = $value->GetPrimaryKeyColumn();
            } else if ($this->columns[$key]->propertyType == 'DateTime') {
                $data[$key] = $value->format('c');
            }
        }

        try {
            $this->dbp->Insert($this->tableName, $data);
        } catch (TableNotFoundException) {
            $this->CreateTable();
            $this->dbp->Insert($this->tableName, $data);
        }
    }
    
    //public function Update($data, $where, $order, $limit);
    
    public function InsertOrUpdate($data) {
        foreach ($data as $key => $value) {
            if (is_subclass_of($this->columns[$key]->propertyType, BaseObject::class)) {
                $data[$key] = $value->GetPrimaryKeyColumn();
            } else if ($this->columns[$key]->propertyType == 'DateTime') {
                $data[$key] = $value->format('c');
            }
        }

        try {
            $this->dbp->InsertOrUpdate($this->tableName, $data, $this->GetPrimaryKeyColumn()->columnName);
        } catch (TableNotFoundException) {
            $this->CreateTable();
            $this->dbp->InsertOrUpdate($this->tableName, $data, $this->GetPrimaryKeyColumn()->columnName);
        }
    }
}