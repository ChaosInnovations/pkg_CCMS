<?php

namespace Package\Pivel\Hydro2\Email\Models;

use Package\Pivel\Hydro2\Email\Views\BaseEmailView;

class EmailMessage
{
    private ?string $htmlBody;
    private ?string $altBody;
    private ?string $subject;

    private array $toAddresses;
    private ?array $ccAddresses;
    private ?array $bccAddresses;

    public function __construct(BaseEmailView $view, array $to, ?array $cc=null, ?array $bcc=null)
    {
        $this->htmlBody = $view->Render();
        $this->altBody = $view->RenderPlaintext();
        $this->subject = $view->GetSubject();

        $this->toAddresses = $to;
        $this->ccAddresses = $cc;
        $this->bccAddresses = $bcc;
    }

    public function SetHTMLBody(string $content) : void {
        $this->htmlBody = $content;
    }

    public function SetAltBody(string $content) : void {
        $this->altBody = $content;
    }
}