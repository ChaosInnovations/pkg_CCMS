<?php

namespace Package\Pivel\Hydro2\AdminPanel\Views;

use Package\Pivel\Hydro2\Core\Views\BaseView;

class MasterDetailNavList extends BaseView
{
    /** @var MasterDetailNavListEntry[] */
    protected array $ListEntries = [];

    public function __construct(
        protected array $NavTree,
    ) {
        foreach ($NavTree as $branch) {
            $this->ListEntries[] = new MasterDetailNavListEntry($branch['key'], $branch['name'], $branch['children']);
        }
    }
}