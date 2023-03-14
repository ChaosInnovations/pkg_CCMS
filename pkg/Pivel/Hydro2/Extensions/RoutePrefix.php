<?php

namespace Package\Pivel\Hydro2\Extensions;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class RoutePrefix
{
    public string $pathPrefix;

    public function __construct(string $pathPrefix)
    {
        $this->pathPrefix = $pathPrefix;
    }
}