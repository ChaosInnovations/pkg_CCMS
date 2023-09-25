<?php

namespace Pivel\Hydro2\Views\Components;

use Pivel\Hydro2\Extensions\RequireScript;
use Pivel\Hydro2\Extensions\RequireStyle;
use Pivel\Hydro2\Views\BaseView;

#[RequireScript('Spinner.js')]
#[RequireStyle('Spinner.css')]
class Spinner extends BaseView
{
    public function __construct()
    {
        
    }
}