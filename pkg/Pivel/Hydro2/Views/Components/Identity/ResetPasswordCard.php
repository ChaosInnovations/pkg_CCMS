<?php

namespace Package\Pivel\Hydro2\Views\Components\Identity;

use Package\Pivel\Hydro2\Extensions\RequireScript;
use Package\Pivel\Hydro2\Extensions\RequireStyle;
use Package\Pivel\Hydro2\Views\Components\MultiPageCard;

#[RequireScript('ResetPasswordCard.js')]
#[RequireStyle('ResetPasswordCard.css')]
class ResetPasswordCard extends MultiPageCard
{
    protected string $ResetPasswordFormId;

    public function __construct(
        protected ?string $Id,
        protected ?string $Heading=null,
        protected ?string $Text=null,
        protected bool $ShowPasswordForm=true,
        protected ?string $ResetToken=null,
        protected ?string $UserId=null,
    ) {
        echo $this->ShowPasswordForm?'show form':'hide form' . "\n";
        $this->ResetPasswordFormId = $Id . '_resetpasswordform';
    }
}