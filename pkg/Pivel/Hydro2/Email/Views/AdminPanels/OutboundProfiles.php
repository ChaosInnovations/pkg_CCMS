<?php

namespace Package\Pivel\Hydro2\Email\Views\AdminPanels;

use Package\Pivel\Hydro2\AdminPanel\Views\BaseAdminPanelViewPage;
use Package\Pivel\Hydro2\Core\Utilities;

class OutboundProfiles extends BaseAdminPanelViewPage
{
    public function __construct(
        protected ?string $Content = null,
    ) {
    }
}