<?php

namespace Package\Pivel\Hydro2\Identity\Views;

use Package\Pivel\Hydro2\Core\Views\BaseWebView;
use Package\Pivel\Hydro2\Identity\Models\PasswordResetToken;

class VerifyView extends BaseWebView
{
    protected string $Text = '';
    protected string $PasswordResetToken = '';

    public function __construct(
        protected bool $IsValid,
        protected bool $IsPasswordChangeRequired = false,
    ) {
        $this->SetIsValid($this->IsValid);
    }

    public function SetIsPasswordChangeRequired(bool $IsPasswordChangeRequired) {
        $this->IsPasswordChangeRequired = $IsPasswordChangeRequired;
    }

    public function SetPasswordResetToken(PasswordResetToken $token) {
        $this->PasswordResetToken = $token->ResetToken;
    }

    public function SetIsValid(bool $IsValid) {
        $this->IsValid = $IsValid;
    }
}