<?php

namespace Package\Pivel\Hydro2\Email\Views\EmailComponents;

use Package\Pivel\Hydro2\Email\Views\BaseEmailView;
use ReflectionClass;

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