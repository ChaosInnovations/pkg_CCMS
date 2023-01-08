<?php

namespace Package\Pivel\Hydro2\Email\Views\EmailComponents;

use Package\Pivel\Hydro2\Core\Views\BaseView;

class ButtonLink extends BaseView
{
    protected string $Url;
    protected string $Content;
    protected string $Color;
    protected string $TextColor;

    public function __construct(string $url, string $content, string $color="#0000ff", string $textColor="#ffffff") {
        $Url = $url;
        $Content = $content;
        $Color = $color;
        $TextColor = $textColor;
    }
}