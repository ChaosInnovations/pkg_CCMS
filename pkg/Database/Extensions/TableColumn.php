<?php

namespace Package\Database\Extensions;

use Attribute;
use Package\Database\Models\Type;

#[Attribute(Attribute::TARGET_PROPERTY)]
class TableColumn {
    public string $columnName;
    public bool $autoIncrement;
    public null|Type $sqlType;

    public function __construct(string $columnName, bool $autoIncrement=false, null|Type $sqlType=null)
    {
        $this->columnName = $columnName;
        $this->autoIncrement = $autoIncrement;
        $this->sqlType = $sqlType;
    }
}