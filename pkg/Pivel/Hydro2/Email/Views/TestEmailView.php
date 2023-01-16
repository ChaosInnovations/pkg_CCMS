<?php

namespace Package\Pivel\Hydro2\Email\Views;

class TestEmailView extends BaseEmailView
{
    protected string $ProfileKey;

    public function __construct(string $profileKey) {
        $this->ProfileKey = $profileKey;
    }
}