<?php

namespace Package\Pivel\Hydro2\Core\Controllers;

use Package\Pivel\Hydro2\Core\Models\Request;

class BaseController
{
    protected Request $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }
}