<?php

namespace Pivel\Hydro2\Views\EmailViews\Identity;

use Pivel\Hydro2\Views\EmailViews\BaseEmailView;

class PasswordChangedNotificationEmailView extends BaseEmailView
{
    protected string $Name;

    public function __construct(string $name) {
        $this->Name = $name;
    }
}