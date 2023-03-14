<?php

namespace Package\Pivel\Hydro2\Views\Components\MasterDetail;

use Package\Pivel\Hydro2\Core\Extensions\RequireScript;
use Package\Pivel\Hydro2\Core\Extensions\RequireStyle;
use Package\Pivel\Hydro2\Core\Views\BaseView;

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