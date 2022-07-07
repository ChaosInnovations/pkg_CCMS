<?php

namespace Package\Database\Extensions;

use Attribute;

#[Attribute]
class DBObjectColumn {
    public string $columnName;

    public function __construct(string $columnName)
    {
        $this->columnName = $columnName;
    }
}