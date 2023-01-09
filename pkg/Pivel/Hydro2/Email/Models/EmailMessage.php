<?php

namespace Package\Pivel\Hydro2\Email\Models;

use Package\Pivel\Hydro2\Core\Views\BaseEmailView;

class EmailMessage
{
    private ?string $htmlBody;
    private ?string $altBody;

    public function __construct(BaseEmailView $view)
    {
        $this->htmlBody = $view->Render();
        $this->altBody = $view->RenderPlaintext();
    }

    public function SetHTMLBody(string $content) : void {
        $this->htmlBody = $content;
    }

    public function SetAltBody(string $content) : void {
        $this->altBody = $content;
    }
}