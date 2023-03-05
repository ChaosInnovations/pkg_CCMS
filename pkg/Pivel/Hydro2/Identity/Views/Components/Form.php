<?php

namespace Package\Pivel\Hydro2\Identity\Views\Components;

use Package\Pivel\Hydro2\Core\Views\BaseView;

class Form extends BaseView
{

    public function __construct(
        protected string $Id,
        protected array $Fields,
    ) {
    }
}