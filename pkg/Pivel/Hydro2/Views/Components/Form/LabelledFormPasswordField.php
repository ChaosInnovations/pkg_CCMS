<?php

namespace Package\Pivel\Hydro2\Views\Components\Form;

use Package\Pivel\Hydro2\Core\Extensions\RequireScript;
use Package\Pivel\Hydro2\Identity\Views\Components\LabelledFormField;


#[RequireScript('LabelledFormPasswordField.js')]
class LabelledFormPasswordField extends LabelledFormField
{

    public function __construct(
        protected ?string $Input=null,
        protected ?string $Name=null,
        protected ?string $IdPrefix=null,
        protected ?string $AutoComplete=null,
        protected ?string $Title=null,
        protected ?string $Label=null,
        protected ?string $Placeholder=null,
    ) {
        $this->IdPrefix ??= bin2hex(random_bytes(16));
    }
}