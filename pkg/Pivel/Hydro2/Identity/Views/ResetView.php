<?php

namespace Package\Pivel\Hydro2\Identity\Views;

use Package\Pivel\Hydro2\Core\Views\BaseWebView;
use Package\Pivel\Hydro2\Identity\Models\PasswordResetToken;

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