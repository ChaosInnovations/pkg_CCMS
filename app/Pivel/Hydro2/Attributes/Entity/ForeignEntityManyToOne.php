<?php

namespace Pivel\Hydro2\Attributes\Entity;

use Attribute;
use Pivel\Hydro2\Models\Database\ReferenceBehaviour;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ForeignEntityManyToOne
{
    public function __construct(
        public ?string $OtherEntityClass = null,
        public ?string $OtherEntityFieldName = null,
        public ReferenceBehaviour $OnUpdate = ReferenceBehaviour::CASCADE,
        public ReferenceBehaviour $OnDelete = ReferenceBehaviour::RESTRICT,
    )
    {
        
    }
}