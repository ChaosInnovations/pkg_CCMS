<?php

namespace Package\Pivel\Hydro2\Email\Models;

use Package\Pivel\Hydro2\Core\Views\BaseView;

class EmailMessage
{
    private ?string $htmlBody;
    private ?string $altBody;

    public function __construct()
    {
        
    }

    public function SetHTMLBody(BaseView $view) : void {
        $this->htmlBody = $view->Render();
    }

    public function SetAltBody(BaseView $view) : void {
        $this->altBody = $view->Render();
    }
}