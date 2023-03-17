<?php

namespace Pivel\Hydro2\Extensions\Database;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class TableName {
    public function __construct(
        public string $tableName,
        ) {
    }
}