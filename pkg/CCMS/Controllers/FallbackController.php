<?php

namespace Package\CCMS\Controllers;

use Package\CCMS\Extensions\Route;
use Package\CCMS\Extensions\RoutePrefix;
use Package\CCMS\Models\HTTP\Method;
use Package\CCMS\Models\HTTP\StatusCode;
use Package\CCMS\Models\Response;

class FallbackController extends BaseController
{
    #[Route(Method::POST, '~{*path}', order:100)]
    public function routeFallback() : Response {
        return new Response(
            content: 'CCMS Core is working, but no packages are loaded.',
            status: StatusCode::OK
        );
    }
}