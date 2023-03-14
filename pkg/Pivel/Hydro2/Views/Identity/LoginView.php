<?php

namespace Package\Pivel\Hydro2\Views\Identity;

use Package\Pivel\Hydro2\Core\Views\BaseWebView;

class LoginView extends BaseWebView
{
    public function __construct(
        public string $DefaultPage='',
    ) {
    }
}