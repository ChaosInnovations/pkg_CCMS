<?php

namespace Package\Pivel\Hydro2\Identity\Views\Components;

use Package\Pivel\Hydro2\Core\Extensions\RequireStyle;
use Package\Pivel\Hydro2\Core\Views\BaseView;

#[RequireStyle('FormInputGroup.css')]
class FormInputGroup extends BaseView
{

    public function __construct(
        protected ?array $Inputs=null,
    ) {
    }
}