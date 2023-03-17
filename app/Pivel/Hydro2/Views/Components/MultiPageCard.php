<?php

namespace Pivel\Hydro2\Views\Components;

use Pivel\Hydro2\Extensions\RequireScript;
use Pivel\Hydro2\Extensions\RequireStyle;
use Pivel\Hydro2\Views\BaseView;

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