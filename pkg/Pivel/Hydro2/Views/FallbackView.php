<?php

namespace Package\Pivel\Hydro2\Views;

use Package\Pivel\Hydro2\Services\Utilities;

class FallbackView extends BaseWebView
{
    

    public function __construct(
        protected ?string $CoreVersion=null,
    ) {
        $v = Utilities::getPackageManifest()['Pivel']['Hydro2']['version'];
        $this->CoreVersion = join('.', $v);
    }
}