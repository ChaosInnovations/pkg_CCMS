<?php

namespace Package\Pivel\Hydro2\Identity\Views\Components;

use Package\Pivel\Hydro2\Core\Extensions\RequireScript;
use Package\Pivel\Hydro2\Core\Extensions\RequireStyle;
use Package\Pivel\Hydro2\Identity\Views\Components\MultiPageCard;

#[RequireScript('LoginCard.js')]
#[RequireStyle('LoginCard.css')]
class LoginCard extends MultiPageCard
{
    protected string $LoginFormId;
    protected string $ChangePasswordFormId;
    protected string $ResetPasswordFormId;

    public function __construct(
        protected ?string $Id,
    ) {
        $this->LoginFormId = $Id . '_loginform';
        $this->ChangePasswordFormId = $Id . '_changepasswordform';
        $this->ResetPasswordFormId = $Id . '_requestresetpasswordform';
    }
}