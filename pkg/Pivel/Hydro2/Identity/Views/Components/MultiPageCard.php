<?php

namespace Package\Pivel\Hydro2\Identity\Views\Components;

use Package\Pivel\Hydro2\Core\Extensions\RequireScript;
use Package\Pivel\Hydro2\Core\Extensions\RequireStyle;
use Package\Pivel\Hydro2\Core\Views\BaseView;

#[RequireScript('MultiPageCard.js')]
#[RequireStyle('MultiPageCard.css')]
class MultiPageCard extends BaseView
{
    public function __construct(
        protected ?string $Id=null,
        protected ?array $Pages=null,
    ) {
        $this->Id ??= bin2hex(random_bytes(16));
    }
}