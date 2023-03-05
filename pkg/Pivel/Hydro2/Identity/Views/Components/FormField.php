<?php

namespace Package\Pivel\Hydro2\Identity\Views\Components;

use Package\Pivel\Hydro2\Core\Extensions\RequireScript;
use Package\Pivel\Hydro2\Core\Extensions\RequireStyle;
use Package\Pivel\Hydro2\Core\Views\BaseView;

#[RequireStyle('FormField.css')]
#[RequireScript('FormField.js')]
class FormField extends BaseView
{

    public function __construct(
        protected ?string $Input=null,
        protected ?string $Name=null,
        protected ?string $Type=null,
        protected ?string $IdPrefix=null,
        protected ?string $AutoComplete=null,
        protected ?string $Title=null,
        protected ?string $Placeholder=null,
        protected ?string $Value=null,
    ) {
        $this->IdPrefix ??= bin2hex(random_bytes(16));
        $this->Placeholder ??= $this->Title;
    }
}