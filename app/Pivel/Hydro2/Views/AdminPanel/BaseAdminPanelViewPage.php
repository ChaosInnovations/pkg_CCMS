<?php

namespace Pivel\Hydro2\Views\AdminPanel;

use Pivel\Hydro2\Views\BaseView;

class BaseAdminPanelViewPage extends BaseView
{
    public function __construct(
        protected ?string $Content = null,
    ) {

    }
}