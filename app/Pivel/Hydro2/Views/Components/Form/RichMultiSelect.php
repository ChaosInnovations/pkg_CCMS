<?php

namespace Pivel\Hydro2\Views\Components\Form;

use Pivel\Hydro2\Extensions\RequireScript;
use Pivel\Hydro2\Extensions\RequireStyle;

#[RequireStyle('RichMultiSelect.css')]
#[RequireScript('RichMultiSelect.js')]
class RichMultiSelect extends FormField
{

    public function __construct(
        protected ?string $Input=null,
        protected ?string $Name=null,
        protected ?string $IdPrefix=null,
        protected ?string $Title=null,
        protected ?string $Label=null,
    ) {
        $this->IdPrefix ??= bin2hex(random_bytes(16));
    }
}