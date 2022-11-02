<?php

namespace Package\Database\Controllers;

use LDAP\Result;
use Package\CCMS\Controllers\BaseController;
use Package\CCMS\Extensions\Route;
use Package\CCMS\Extensions\RoutePrefix;
use Package\CCMS\Models\HTTP\Method;
use Package\CCMS\Models\HTTP\StatusCode;
use Package\CCMS\Models\Response;
use Package\Database\Services\DatabaseService;
use Package\Database\Views\SetupView;

class SetupController extends BaseController
{
    // These endpoints should only be enabled if the database configuration has not yet been set up.

    #[Route(Method::GET, 'setup')]
    public function SetupStart() : Response {
        // check if we should do startup flow, otherwise return empty Response
        if (DatabaseService::Instance()->CheckConfiguration()) {
            return Response::GetEmpty();
        }

        // Finally, return Database Setup view
        $view = new SetupView();
        return new Response(
            content: $view->Render(),
            status: StatusCode::OK
        );
    }
}