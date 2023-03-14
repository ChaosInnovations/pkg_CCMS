<?php

namespace Package\Pivel\Hydro2\Extensions\Database;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class TablePrimaryKey {
    public function __construct()
    {
    }
}