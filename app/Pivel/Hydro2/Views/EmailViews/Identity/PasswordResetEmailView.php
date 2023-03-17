<?php

namespace Pivel\Hydro2\Views\EmailViews\Identity;

use Pivel\Hydro2\Views\EmailViews\BaseEmailView;

class PasswordResetEmailView extends BaseEmailView
{
    protected string $ResetUrl;
    protected string $Name;
    protected string $ValidForString;

    public function __construct(string $resetUrl, string $name, int $validForMinutes) {
        $this->ResetUrl = $resetUrl;
        $this->Name = $name;
        $this->ValidForString = $validForMinutes.' minute'.($validForMinutes===1?'':'s');
    }
}