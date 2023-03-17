<?php

namespace Pivel\Hydro2\Views\Components\Email;

use Pivel\Hydro2\Views\EmailViews\BaseEmailView;

class ButtonLink extends BaseEmailView
{
    public function __construct(
        protected string $Url,
        protected string $Content,
        protected ?string $Color="#0000ff",
        protected ?string $TextColor="#ffffff",
    ) {
    }
}