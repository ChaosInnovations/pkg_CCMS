<?php

namespace Package\Pivel\Hydro2\AdminPanel\Controllers;

use Package\Pivel\Hydro2\AdminPanel\Views\AdminPanelView;
use Package\Pivel\Hydro2\Core\Controllers\BaseController;
use Package\Pivel\Hydro2\Core\Extensions\Route;
use Package\Pivel\Hydro2\Core\Extensions\RoutePrefix;
use Package\Pivel\Hydro2\Core\Models\HTTP\Method;
use Package\Pivel\Hydro2\Core\Models\HTTP\StatusCode;
use Package\Pivel\Hydro2\Core\Models\Response;
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

        $view = new AdminPanelView([], 'hydro2');
        return new Response(
            content: $view->Render(),
        );
    }
}