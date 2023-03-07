<?php

namespace Package\Pivel\Hydro2\AdminPanel\Views;

use Package\Pivel\Hydro2\Core\Views\BaseView;

class MasterDetailNavListEntry extends BaseView
{
    protected bool $HasChildren = false;
    protected ?MasterDetailNavList $NavList = null;

    public function __construct(
        protected string $Key,
        protected string $Name,
        protected array $ChildNavTree,
    ) {
        if (count($this->ChildNavTree) != 0) {
            $this->HasChildren = true;
            $this->NavList = new MasterDetailNavList($this->ChildNavTree);
        }
    }
}