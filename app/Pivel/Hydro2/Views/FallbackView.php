<?php

namespace Pivel\Hydro2\Views;

use Pivel\Hydro2\Hydro2;
use Pivel\Hydro2\Services\PackageManifestService;

class FallbackView extends BaseWebView
{
    public function __construct(
        protected ?string $CoreVersion=null,
    ) {
        $manifestService = Hydro2::$Current->ResolveDependency(PackageManifestService::class);
        $v = $manifestService->GetPackageManifest()['Pivel']['Hydro2']['version'];
        $this->CoreVersion = join('.', $v);
    }
}