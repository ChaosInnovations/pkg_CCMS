<?php

namespace Package\Pivel\Hydro2\Views\Components\Form;

use Package\Pivel\Hydro2\Extensions\RequireStyle;
use Package\Pivel\Hydro2\Views\BaseView;

#[RequireStyle('FormInputGroup.css')]
class FormInputGroup extends BaseView
{

    public function __construct(
        protected ?array $Inputs=null,
    ) {
    }
}