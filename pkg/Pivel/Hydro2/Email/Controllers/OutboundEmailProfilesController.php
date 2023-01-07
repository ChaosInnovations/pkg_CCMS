<?php

namespace Package\Pivel\Hydro2\Database\Controllers;

use Package\Pivel\Hydro2\Core\Controllers\BaseController;
use Package\Pivel\Hydro2\Core\Extensions\Route;
use Package\Pivel\Hydro2\Core\Extensions\RoutePrefix;
use Package\Pivel\Hydro2\Core\Models\HTTP\Method;
use Package\Pivel\Hydro2\Core\Models\HTTP\StatusCode;
use Package\Pivel\Hydro2\Core\Models\JsonResponse;
use Package\Pivel\Hydro2\Core\Models\Response;
use Package\Pivel\Hydro2\Database\Models\DatabaseConfigurationProfile;
use Package\Pivel\Hydro2\Database\Services\DatabaseService;
use Package\Pivel\Hydro2\Email\Models\OutboundEmailProfile;
use Package\Pivel\Hydro2\Email\Services\EmailService;

#[RoutePrefix('api/hydro2/core/email/outboundprofiles')]
class SettingsController extends BaseController
{
    private function UserHasPermission(string $permission) : bool {
        return false;
    }

    #[Route(Method::GET, '')]
    public function GetAllProfiles() : Response
    {
        // if database has already been configured and not logged in as admin, return 404
        if (!$this->UserHasPermission("manageoutboundemailprofiles")) {
            return new Response(
                status: StatusCode::NotFound
            );
        }

        $profiles = OutboundEmailProfile::GetAll();
        $serializedProfiles = [];
        foreach ($profiles as $profile) {
            $serializedProfiles[] = [
                'key' => $profile->Key,
                'name' => $profile->Name,
                'type' => $profile->Type,
            ];
        }

        return new JsonResponse(
            data: [
                'outboundemailprofiles' => $serializedProfiles,
            ],
            status: StatusCode::OK,
        );
    }

    #[Route(Method::POST, 'getproviders')]
    public function GetProviders() : Response
    {
        // if not logged in as admin, return 404
        if (!$this->UserHasPermission("manageoutboundemailprofiles")) {
            return new Response(
                status: StatusCode::NotFound
            );
        }

        return new JsonResponse(
            data: [
                'emailproviders' => EmailService::GetAvailableProviders(),
            ],
            status: StatusCode::OK,
        );
    }

    #[Route(Method::POST, '')]
    #[Route(Method::POST, 'create')]
    public function CreateProfile() : Response {
        return new Response(
            status: StatusCode::NotImplemented
        );
    }

    #[Route(Method::GET, '{key}')]
    public function GetProfile() : Response {
        // if database has already been configured and not logged in as admin, return 404
        if (!$this->UserHasPermission("manageoutboundemailprofiles")) {
            return new Response(
                status: StatusCode::NotFound
            );
        }

        $profile = OutboundEmailProfile::LoadFromKey($this->request->Args['key']);
        $serializedProfiles = [
            [
                'key' => $profile->Key,
                'name' => $profile->Name,
                'type' => $profile->Type,
            ]
        ];

        return new JsonResponse(
            data: [
                'outboundemailprofiles' => $serializedProfiles,
            ],
            status: StatusCode::OK,
        );
    }

    #[Route(Method::POST, '{key}')]
    #[Route(Method::POST, '{key}/update')]
    public function UpdateProfile() : Response {
        return new Response(
            status: StatusCode::NotImplemented
        );
    }

    #[Route(Method::POST, '{key}/remove')]
    #[Route(Method::DELETE, '{key}/remove')]
    public function DeleteProfile() : Response {
        return new Response(
            status: StatusCode::NotImplemented
        );
    }

    #[Route(Method::GET, '{key}/test')]
    public function TestProfile() : Response {
        return new Response(
            status: StatusCode::NotImplemented
        );
    }
}