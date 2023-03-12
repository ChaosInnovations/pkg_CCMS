<?php

namespace Package\Pivel\Hydro2\AdminPanel\Controllers;

use Package\Pivel\Hydro2\AdminPanel\Views\AdminPanelView;
use Package\Pivel\Hydro2\AdminPanel\Views\BaseAdminPanelViewPage;
use Package\Pivel\Hydro2\Core\Controllers\BaseController;
use Package\Pivel\Hydro2\Core\Extensions\Route;
use Package\Pivel\Hydro2\Core\Extensions\RoutePrefix;
use Package\Pivel\Hydro2\Core\Models\HTTP\Method;
use Package\Pivel\Hydro2\Core\Models\HTTP\StatusCode;
use Package\Pivel\Hydro2\Core\Models\Response;
use Package\Pivel\Hydro2\Core\Utilities;
use Package\Pivel\Hydro2\Core\Views\FallbackView;
use Package\Pivel\Hydro2\Identity\Services\IdentityService;

class AdminPanelController extends BaseController
{
    #[Route(Method::GET, 'admin/{*path}')]
    #[Route(Method::GET, 'admin')]
    public function GetAdminPanelView() : Response {
        if (IdentityService::GetRequestSession($this->request) === false) {
            return new Response(
                status: StatusCode::Found,
                headers: [
                    'Location' => '/login?next='.urlencode($this->request->fullUrl),
                ],
            );
        }

        $nodes = [];
        $packageManifest = Utilities::getPackageManifest();
        foreach ($packageManifest as $vendorName => $vendorPackages) {
            foreach ($vendorPackages as $packageName => $package) {
                if (!isset($package['admin_panel_nodes'])) {
                    continue;
                }

                // pivel/hydro2/viewusers

                foreach ($package['admin_panel_nodes'] as $node) {
                    $nodes[$node['key']] = [
                        'name' => $node['name'],
                    ];

                    if (!isset($node['view'])) {
                        continue;
                    }

                    if (!is_subclass_of($node['view'], BaseAdminPanelViewPage::class)) {
                        $nodes[$node['key']]['view'] = new BaseAdminPanelViewPage();
                    } else {
                        $nodes[$node['key']]['view'] = new $node['view']();
                    }
                }
            }
        }

        $view = new AdminPanelView($nodes, $this->request->Args['path']??'');
        return new Response(
            content: $view->Render(),
        );
    }
}