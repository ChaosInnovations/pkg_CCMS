<?php

namespace Package\Database\Extensions;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class TablePrimaryKey {
    public function __construct()
    {
    }
}