<?php

namespace Package\Pivel\Hydro2\Views\Identity;

use Package\Pivel\Hydro2\Models\Identity\PasswordResetToken;
use Package\Pivel\Hydro2\Views\BaseWebView;

class ResetView extends BaseWebView
{
    protected string $PasswordResetToken = '';
    protected string $UserId = '';

    public function __construct(
        protected bool $IsValid,
    ) {
        $this->SetIsValid($this->IsValid);
    }

    public function SetPasswordResetToken(PasswordResetToken $token) {
        $this->PasswordResetToken = $token->ResetToken;
    }

    public function SetIsValid(bool $IsValid) {
        $this->IsValid = $IsValid;
    }

    public function SetUserId(string $Id) {
        $this->UserId = $Id;
    }
}