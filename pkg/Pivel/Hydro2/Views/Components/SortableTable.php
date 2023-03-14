<?php

namespace Package\Pivel\Hydro2\Views\Components;

use Package\Pivel\Hydro2\Extensions\RequireScript;
use Package\Pivel\Hydro2\Extensions\RequireStyle;
use Package\Pivel\Hydro2\Views\BaseView;

#[RequireScript('SortableTable.js')]
#[RequireStyle('SortableTable.css')]
class SortableTable extends BaseView
{
    public function __construct(
        protected string $Id,
        protected array $Headers,
    )
    {
        
    }
}