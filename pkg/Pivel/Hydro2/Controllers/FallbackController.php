<?php

namespace Package\Pivel\Hydro2\Controllers;

use Package\Pivel\Hydro2\Models\HTTP\Method;
use Package\Pivel\Hydro2\Models\HTTP\StatusCode;
use Package\Pivel\Hydro2\Extensions\Route;
use Package\Pivel\Hydro2\Models\HTTP\Response;
use Package\Pivel\Hydro2\Views\FallbackView;

class FallbackController extends BaseController
{
    #[Route(Method::POST, '', order:100)]
    #[Route(Method::GET, '', order:100)]
    public function routeFallback() : Response {
        $view = new FallbackView();
        return new Response(
            content: $view->Render(),
            status: StatusCode::OK
        );
    }

    #[Route(Method::POST, '~{*path}', order:200)]
    #[Route(Method::GET, '~{*path}', order:200)]
    public function routeNotFound() : Response {
        //$view = new NotFoundView();
        return new Response(
            //content: $view->Render(),
            status: StatusCode::NotFound
        );
    }
}