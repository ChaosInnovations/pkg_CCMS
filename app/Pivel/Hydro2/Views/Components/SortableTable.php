<?php

namespace Pivel\Hydro2\Views\Components;

use Pivel\Hydro2\Extensions\RequireScript;
use Pivel\Hydro2\Extensions\RequireStyle;
use Pivel\Hydro2\Views\BaseView;

#[RequireScript('SortableTable.js')]
#[RequireStyle('SortableTable.css')]
class SortableTable extends BaseView
{
    public function __construct(
        protected string $Id,
        protected array $Headers,
    ) {
        
    }
}