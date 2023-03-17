<?php

namespace Pivel\Hydro2\Views\Components\MasterDetail;

use Pivel\Hydro2\Views\BaseView;

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