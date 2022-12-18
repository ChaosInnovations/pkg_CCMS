<?php

namespace Package\Database\Extensions;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class TableName {
    public string $tableName;

    public function __construct(string $tableName)
    {
        $this->tableName = $tableName;
    }
}