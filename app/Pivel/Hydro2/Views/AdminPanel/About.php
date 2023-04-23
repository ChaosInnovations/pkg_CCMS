<?php

namespace Pivel\Hydro2\Views\AdminPanel;

use Pivel\Hydro2\Hydro2;
use Pivel\Hydro2\Services\PackageManifestService;

class About extends BaseAdminPanelViewPage
{
    protected string $AboutWebsite = '';
    protected string $Version = '';
    protected string $ReleaseDate = '';
    protected string $PHPVersion = '';
    protected string $WebServer = '';
    protected string $InstallDate = '';
    protected int $NumPackages = 0;
    protected bool $NumPackagesPlural = true;
    protected array $PackageTableRows = [];

    public function __construct(
        protected ?string $Content = null,
    ) {
        $manifestService = Hydro2::$Current->ResolveDependency(PackageManifestService::class);
        $manifest = $manifestService->GetPackageManifest();
        $h2manifest = $manifest['Pivel']['Hydro2'];

        $this->AboutWebsite = $h2manifest['author']['website'];
        $this->Version = implode('.', $h2manifest['version']);
        $this->ReleaseDate = $h2manifest['release_date'];
        $this->PHPVersion = phpversion();
        $this->WebServer = $_SERVER['SERVER_SOFTWARE'];

        // TODO get this information from future implementation of global configuration
        $this->InstallDate = 'Not implemented';

        $this->NumPackages = 0;
        $this->PackageTableRows = [];
        foreach ($manifest as $vendorName => $vendorPackages) {
            foreach ($vendorPackages as $packageName => $package) {
                $this->NumPackages++;
                $version = implode('.', $package['version']);
                // TODO make this a view.
                $this->PackageTableRows[] = "<tr><td>{$package['vendor']}</td><td><a href=\"{$package['author']['website']}\" title=\"{$package['name']}\">{$package['name']}</a></td><td>{$version}</td><td>{$package['description']}</td></tr>";
            }
        }

        $this->NumPackagesPlural = $this->NumPackages !== 1;
    }
}