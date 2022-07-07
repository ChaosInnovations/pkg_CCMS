<?php

namespace Package\CCMS\Extensions;

use Attribute;
use Package\CCMS\Models\HTTP\Method;

#[Attribute]
class RouteURI {
    public Method $method;
    public string $uri;

    public function __construct(Method $method, string $uri)
    {
        $this->method = $method;
        $this->uri = $uri;
    }
}