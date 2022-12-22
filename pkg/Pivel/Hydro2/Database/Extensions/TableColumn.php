<?php

namespace Package\Pivel\Hydro2\Database\Extensions;

use Attribute;
use Package\Pivel\Hydro2\Database\Models\Type;

#[Attribute(Attribute::TARGET_PROPERTY)]
class TableColumn {
    public function __construct(
        public string $columnName,
        public bool $autoIncrement=false,
        public null|Type $sqlType=null,
    ) {
    }
}