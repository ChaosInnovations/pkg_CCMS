<?php

namespace Pivel\Hydro2\Views\Components\Form;

use Pivel\Hydro2\Views\BaseView;

class Form extends BaseView
{

    public function __construct(
        protected string $Id,
        protected array $Fields,
    ) {
    }
}