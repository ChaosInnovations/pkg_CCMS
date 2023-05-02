<?php

namespace Pivel\Hydro2\Attributes\Entity;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ForeignEntityOneToMany
{
    public function __construct(
        public string $OtherEntityClass,
        public ?string $OtherEntityFieldName = null,
    ) {
    }
}