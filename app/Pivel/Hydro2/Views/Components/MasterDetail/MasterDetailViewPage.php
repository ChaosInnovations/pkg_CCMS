<?php

namespace Pivel\Hydro2\Views\Components\MasterDetail;

use Pivel\Hydro2\Views\BaseView;

class MasterDetailViewPage extends BaseView
{
    public function __construct(
        protected string $Key,
        protected BaseView $Content,
    ) {

    }
}