<?php

namespace Package\Pivel\Hydro2\Views\Components\Form;

use Package\Pivel\Hydro2\Extensions\RequireStyle;

#[RequireStyle('LabelledFormField.css')]
class LabelledFormField extends FormField
{

    public function __construct(
        protected ?string $Input=null,
        protected ?string $Name=null,
        protected ?string $Type=null,
        protected ?string $IdPrefix=null,
        protected ?string $AutoComplete=null,
        protected ?string $Title=null,
        protected ?string $Label=null,
        protected ?string $Placeholder=null,
        protected ?string $Value=null,
    ) {
        $this->IdPrefix ??= bin2hex(random_bytes(16));
    }
}