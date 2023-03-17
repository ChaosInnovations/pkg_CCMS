<?php

namespace Pivel\Hydro2\Views\Identity;

use Pivel\Hydro2\Views\BaseWebView;

class LoginView extends BaseWebView
{
    public function __construct(
        public string $DefaultPage='',
    ) {
    }
}