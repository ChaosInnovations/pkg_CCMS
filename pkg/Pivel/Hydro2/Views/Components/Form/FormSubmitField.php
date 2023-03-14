<?php

namespace Package\Pivel\Hydro2\Views\Components\Form;

class FormSubmitField extends FormField
{

    public function __construct(
        protected ?string $IdPrefix=null,
        protected ?string $Content=null,
    ) {
        $this->IdPrefix ??= bin2hex(random_bytes(16));
    }
}