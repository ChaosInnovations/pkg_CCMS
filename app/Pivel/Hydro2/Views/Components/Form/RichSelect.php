<?php

namespace Pivel\Hydro2\Views\Components\Form;

use Pivel\Hydro2\Extensions\RequireScript;
use Pivel\Hydro2\Extensions\RequireStyle;

#[RequireStyle('RichSelect.css')]
#[RequireScript('RichSelect.js')]
class RichSelect extends FormField
{

    public function __construct(
        protected ?string $Input=null,
        protected ?string $Name=null,
        protected ?string $IdPrefix=null,
        protected ?string $Title=null,
        protected ?string $Label=null,
        protected bool $Multiple=false,
    ) {
        $this->IdPrefix ??= bin2hex(random_bytes(16));
    }
}