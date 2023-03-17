<?php

namespace Pivel\Hydro2\Views\Components\MasterDetail;

use Pivel\Hydro2\Extensions\RequireScript;
use Pivel\Hydro2\Extensions\RequireStyle;
use Pivel\Hydro2\Views\BaseView;

#[RequireScript('MasterDetailView.js')]
#[RequireStyle('MasterDetailView.css')]
class MasterDetailView extends BaseView
{
    /** @var MasterDetailNavList */
    protected MasterDetailNavList $NavList;

    public function __construct(
        protected string $Id,
        protected string $Title,
        protected array $NavTree,
        protected array $ContentPages,
    ) {
        $this->NavList = new MasterDetailNavList($NavTree);
    }
}