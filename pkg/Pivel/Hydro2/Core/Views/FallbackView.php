<?php

namespace Package\Pivel\Hydro2\Core\Views;

use Package\Pivel\Hydro2\Core\Utilities;

class FallbackView extends BaseView
{
    protected string $coreVersion;

    public function __construct() {
        $v = Utilities::getPackageManifest()['CCMS']['version'];
        $this->coreVersion = join('.', $v);
    }
}