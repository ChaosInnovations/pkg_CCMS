<?php

namespace Package\CCMS\Controllers;

use Package\CCMS\Extensions\Route;
use Package\CCMS\Extensions\RoutePrefix;
use Package\CCMS\Models\HTTP\Method;
use Package\CCMS\Models\HTTP\StatusCode;
use Package\CCMS\Models\Response;
use Package\CCMS\Views\FallbackView;

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
}