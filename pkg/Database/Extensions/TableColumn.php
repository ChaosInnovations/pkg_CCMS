<?php

namespace Package\Database\Extensions;

use Attribute;

#[Attribute]
class TableColumn {
    public string $columnName;
    public bool $autoIncrement;

    public function __construct(string $columnName, bool $autoIncrement=false)
    {
        $this->columnName = $columnName;
        $this->autoIncrement = $autoIncrement;
    }
}