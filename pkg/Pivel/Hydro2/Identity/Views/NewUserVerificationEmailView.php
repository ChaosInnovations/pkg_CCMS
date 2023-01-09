<?php

namespace Package\Pivel\Hydro2\Identity\Views;

use Package\Pivel\Hydro2\Core\Views\BaseEmailView;

class NewUserVerificationEmailView extends BaseEmailView
{
    protected string $VerifyUrl;
    protected string $Name;

    public function __construct(string $verifyUrl, string $name) {
        $VerifyUrl = $verifyUrl;
        $Name = $name;
    }
}