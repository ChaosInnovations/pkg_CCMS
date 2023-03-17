<?php

namespace Pivel\Hydro2\Views\EmailViews\Identity;

use Pivel\Hydro2\Views\EmailViews\BaseEmailView;

class NewEmailNotificationEmailView extends BaseEmailView
{
    protected string $Name;
    protected string $OldEmail;
    protected string $NewEmail;

    public function __construct(string $name, string $oldEmail, string $newEmail) {
        $this->Name = $name;
        $this->OldEmail = $oldEmail;
        $this->NewEmail = $newEmail;
    }
}