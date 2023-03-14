<?php

namespace Package\Pivel\Hydro2\Views\Components\MasterDetail;

use Package\Pivel\Hydro2\Core\Views\BaseView;

class MasterDetailViewPage extends BaseView
{
    public function __construct(
        protected string $Key,
        protected BaseView $Content,
    ) {

    }
}