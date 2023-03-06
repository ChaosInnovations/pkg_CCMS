<?php

namespace Package\Pivel\Hydro2\AdminPanel\Views;

use Package\Pivel\Hydro2\Core\Views\BaseWebView;
use Package\Pivel\Hydro2\Identity\Models\PasswordResetToken;

class AdminPanelView extends BaseWebView
{
    public function __construct(
        protected array $Nodes,
        protected string $DefaultNode = '',
    ) {
        
    }
}