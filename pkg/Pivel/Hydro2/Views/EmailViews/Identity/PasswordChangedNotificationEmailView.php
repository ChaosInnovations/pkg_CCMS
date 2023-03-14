<?php

namespace Package\Pivel\Hydro2\Views\EmailViews\Identity;

use Package\Pivel\Hydro2\Email\Views\BaseEmailView;

class PasswordChangedNotificationEmailView extends BaseEmailView
{
    protected string $Name;

    public function __construct(string $name) {
        $this->Name = $name;
    }
}