<?php

namespace Package\Pivel\Hydro2\Identity\Views\EmailViews;

use Package\Pivel\Hydro2\Email\Views\BaseEmailView;

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