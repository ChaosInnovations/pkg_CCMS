<?php

namespace Package\Pivel\Hydro2\Extensions\Database;

use Attribute;

// TODO Implement the functionality for this
#[Attribute(Attribute::TARGET_PROPERTY)]
class ChildTable {
    public function __construct(
        public string $className,
        ) {
    }
}