<?php

namespace Package\Pivel\Hydro2\Views\AdminPanel;

use Package\Pivel\Hydro2\AdminPanel\Views\BaseAdminPanelViewPage;
use Package\Pivel\Hydro2\Core\Utilities;

class ConnectionProfiles extends BaseAdminPanelViewPage
{
    public function __construct(
        protected ?string $Content = null,
    ) {
    }
}