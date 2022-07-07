<?php

namespace Package\Database\Extensions;

use Attribute;

#[Attribute]
class DBObject {
    public string $tableName;

    public function __construct(string $tableName)
    {
        $this->tableName = $tableName;
    }
}