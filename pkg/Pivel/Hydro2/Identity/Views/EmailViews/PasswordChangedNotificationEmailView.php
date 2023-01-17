<?php

namespace Package\Pivel\Hydro2\Identity\Views\EmailViews;

use Package\Pivel\Hydro2\Email\Views\BaseEmailView;

class PasswordChangedNotificationEmailView extends BaseEmailView
{
    protected string $Name;

    public function __construct(string $name) {
        $this->Name = $name;
    }
}