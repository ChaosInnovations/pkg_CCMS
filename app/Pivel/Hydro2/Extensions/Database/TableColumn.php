<?php

namespace Pivel\Hydro2\Extensions\Database;

use Attribute;
use Pivel\Hydro2\Models\Database\Type;

#[Attribute(Attribute::TARGET_PROPERTY)]
class TableColumn {
    public function __construct(
        public string $columnName,
        public bool $autoIncrement=false,
        public null|Type $sqlType=null,
    ) {
    }
}