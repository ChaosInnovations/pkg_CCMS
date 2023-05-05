<?php

namespace Pivel\Hydro2\Extensions;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class RoutePrefix
{
    public function __construct(
        public string $pathPrefix
    ) {
    }
}