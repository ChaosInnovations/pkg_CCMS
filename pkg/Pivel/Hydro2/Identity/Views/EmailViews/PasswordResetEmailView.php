<?php

namespace Package\Pivel\Hydro2\Identity\Views\EmailViews;

use Package\Pivel\Hydro2\Email\Views\BaseEmailView;

class PasswordResetEmailView extends BaseEmailView
{
    protected string $ResetUrl;
    protected string $Name;
    protected int $ValidForString;

    public function __construct(string $resetUrl, string $name, string $validForMinutes) {
        $this->ResetUrl = $resetUrl;
        $this->Name = $name;
        $this->ValidForString = $validForMinutes.' minute'.($validForMinutes===1?'':'s');
    }
}