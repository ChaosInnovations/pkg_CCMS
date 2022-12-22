<?php

namespace Package\Pivel\Hydro2\Database\Extensions;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class TableName {
    public function __construct(
        public string $tableName,
        ) {
    }
}