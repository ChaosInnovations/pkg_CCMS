<?php

namespace Package\CCMS\Controllers;

use Package\CCMS\Models\Request;

class BaseController
{
    protected Request $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }
}