<?php

namespace Package\Pivel\Hydro2\Database\Extensions;

use Attribute;
use Package\Pivel\Hydro2\Database\Models\ReferenceBehaviour;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ChildTable {
    public function __construct(
        public string $tableName,
        ) {
    }
}