<?php

namespace Package\Pivel\Hydro2\Database\Views\AdminPanels;

use Package\Pivel\Hydro2\AdminPanel\Views\BaseAdminPanelViewPage;
use Package\Pivel\Hydro2\Core\Utilities;

class ConnectionProfiles extends BaseAdminPanelViewPage
{
    public function __construct(
        protected ?string $Content = null,
    ) {
    }
}