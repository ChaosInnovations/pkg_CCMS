<?php

namespace Package\Pivel\Hydro2\Controllers;

use Package\Pivel\Hydro2\Models\HTTP\Request;

class BaseController
{
    protected Request $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }
}