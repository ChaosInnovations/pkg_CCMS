<?php

namespace Package\Pivel\Hydro2\Views\EmailViews\Identity;

use Package\Pivel\Hydro2\Email\Views\BaseEmailView;

class NewUserVerificationEmailView extends BaseEmailView
{
    protected string $VerifyUrl;
    protected string $Name;

    public function __construct(string $verifyUrl, string $name) {
        $this->VerifyUrl = $verifyUrl;
        $this->Name = $name;
    }
}