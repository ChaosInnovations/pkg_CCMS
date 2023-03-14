<?php

namespace Package\Pivel\Hydro2\Controllers;

use Package\Pivel\Hydro2\Models\HTTP\Method;
use Package\Pivel\Hydro2\Models\HTTP\StatusCode;
use Package\Pivel\Hydro2\Extensions\Route;
use Package\Pivel\Hydro2\Models\HTTP\Response;
use Package\Pivel\Hydro2\Services\IdentityService;
use Package\Pivel\Hydro2\Services\Utilities;
use Package\Pivel\Hydro2\Views\AdminPanel\AdminPanelView;
use Package\Pivel\Hydro2\Views\AdminPanel\BaseAdminPanelViewPage;

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

        // check whether the session/user is allowed to view the admin panel at all.
        if (!IdentityService::GetRequestUser($this->request)->Role->HasPermission('pivel/hydro2/viewadminpanel')) {
            return new Response(
                status: StatusCode::Forbidden,
            );
        }

        $nodes = [];
        $packageManifest = Utilities::getPackageManifest();
        foreach ($packageManifest as $vendorName => $vendorPackages) {
            foreach ($vendorPackages as $packageName => $package) {
                if (!isset($package['admin_panel_nodes'])) {
                    continue;
                }

                foreach ($package['admin_panel_nodes'] as $node) {
                    // check whether user has permission to view this node. If no permissions are specified, user is permitted.
                    if (isset($node['requires']) && !empty($node['requires'])) {
                        if (!is_array($node['requires'])) {
                            $node['requires'] = [$node['requires']];
                        }
                        $permitted = false;
                        foreach ($node['requires'] as $requiredPermission) {
                            $permitted |= IdentityService::GetRequestUser($this->request)->Role->HasPermission($requiredPermission);
                            if ($permitted) {
                                break;
                            }
                        }
                        if (!$permitted) {
                            continue;
                        }
                    }

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