<?php

namespace Package\Database\Extensions;

use Attribute;
use Package\Database\Models\ReferenceBehaviour;

#[Attribute(Attribute::TARGET_PROPERTY)]
class TableForeignKey {
    public function __construct(
        public ReferenceBehaviour $onUpdate = ReferenceBehaviour::RESTRICT,
        public ReferenceBehaviour $onDelete = ReferenceBehaviour::RESTRICT,
    ) {
    }
}