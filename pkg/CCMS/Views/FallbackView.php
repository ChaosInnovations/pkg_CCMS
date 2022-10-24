<?php

namespace Package\CCMS\Views;

use Package\CCMS\Utilities;

class FallbackView extends BaseView
{
    protected string $coreVersion;

    public function __construct() {
        $v = Utilities::getPackageManifest()['CCMS']['module_data']['version'];
        $this->coreVersion = join('.', $v);
    }
}