<?php

namespace Package\Pivel\Hydro2\Views\AdminPanel;

use Package\Pivel\Hydro2\Views\BaseView;

class BaseAdminPanelViewPage extends BaseView
{
    public function __construct(
        protected ?string $Content = null,
    ) {

    }
}