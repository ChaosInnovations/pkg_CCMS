<?php

namespace Package\Pivel\Hydro2\Core\Extensions;

use Attribute;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_CLASS)]
class RequireStyle {

    public function __construct(
        public string $Path,
        public bool $Inline=true)
    {
    }
}