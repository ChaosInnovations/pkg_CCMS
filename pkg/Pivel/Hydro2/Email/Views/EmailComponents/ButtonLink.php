<?php

namespace Package\Pivel\Hydro2\Email\Views\EmailComponents;

use Package\Pivel\Hydro2\Email\Views\BaseEmailView;

class ButtonLink extends BaseEmailView
{
    protected string $Url;
    protected string $Content;
    protected string $Color;
    protected string $TextColor;

    public function __construct(string $url, string $content, string $color="#0000ff", string $textColor="#ffffff") {
        $this->Url = $url;
        $this->Content = $content;
        $this->Color = $color;
        $this->TextColor = $textColor;
    }
}