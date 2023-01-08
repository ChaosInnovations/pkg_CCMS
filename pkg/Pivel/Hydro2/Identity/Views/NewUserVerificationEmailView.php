<?php

namespace Package\Pivel\Hydro2\Identity\Views;

use Package\Pivel\Hydro2\Core\Views\BaseView;

class NewUserVerificationEmailView extends BaseView
{
    protected string $VerifyUrl;
    protected string $Name;

    public function __construct(string $verifyUrl, string $name) {
        $VerifyUrl = $verifyUrl;
        $Name = $name;
    }
}