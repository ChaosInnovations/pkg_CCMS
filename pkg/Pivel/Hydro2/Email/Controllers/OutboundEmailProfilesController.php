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
use Package\Pivel\Hydro2\Email\Models\EmailMessage;
use Package\Pivel\Hydro2\Email\Models\OutboundEmailProfile;
use Package\Pivel\Hydro2\Email\Services\EmailService;
use Package\Pivel\Hydro2\Email\Views\TestEmailView;

#[RoutePrefix('api/hydro2/core/email/outboundprofiles')]
class SettingsController extends BaseController
{
    // TODO replace with real permission check
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
                'label' => $profile->Label,
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
        if (!$this->UserHasPermission("manageoutboundemailprofiles")) {
            return new Response(
                status: StatusCode::NotFound
            );
        }

        if (!isset($this->request->Args['key'])) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'key',
                            'description' => 'Unique key for profile',
                            'message' => 'A unique key for this outbound email profile is required.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are missing.'
            );
        }

        if (OutboundEmailProfile::LoadFromKey($this->request->Args['key']) !== null) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'key',
                            'description' => 'Unique key for profile',
                            'message' => 'An outbound email profile already exists with the provided key.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are invalid.'
            );
        }
        
        $profile = new OutboundEmailProfile(
            key: $this->request->Args['key'],
            label: $this->request->Args['label']??'Unnamed Email Profile',
            type: $this->request->Args['type']??'smtp',
            senderAddress: $this->request->Args['sender_address']??'',
            senderName: $this->request->Args['sender_name']??'',
            requireAuth: $this->request->Args['require_auth']??false,
            username: $this->request->Args['username']??null,
            password: $this->request->Args['password']??null,
            host: $this->request->Args['host']??'localhost',
            port: $this->request->Args['port']??465,
        );

        if (!$profile->Save()) {
            return new JsonResponse(
                status: StatusCode::InternalServerError,
                error_message: "There was a problem with the database."
            );
        }

        return new JsonResponse(status:StatusCode::OK);
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
        if ($profile === null) {
            return new JsonResponse(
                data: [
                    'outboundemailprofiles' => [],
                ],
                status: StatusCode::OK,
            );
        }

        $serializedProfiles = [
            [
                'key' => $profile->Key,
                'label' => $profile->Label,
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
        if (!$this->UserHasPermission("manageoutboundemailprofiles")) {
            return new Response(
                status: StatusCode::NotFound
            );
        }

        $profile = OutboundEmailProfile::LoadFromKey($this->request->Args['key']);
        if ($profile === null) {
            $profile = new OutboundEmailProfile(
                key: $this->request->Args['key'],
            );
        }
        
        $profile->Label = $this->request->Args['label']??'Unnamed Email Profile';
        $profile->Type = $this->request->Args['type']??'smtp';
        $profile->SenderAddress = $this->request->Args['sender_address']??'';
        $profile->SenderName = $this->request->Args['sender_name']??'';
        $profile->RequireAuth = $this->request->Args['require_auth']??false;
        $profile->Username = $this->request->Args['username']??null;
        $profile->Password = $this->request->Args['password']??null;
        $profile->Host = $this->request->Args['host']??'localhost';
        $profile->Port = $this->request->Args['port']??465;

        if (!$profile->Save()) {
            return new JsonResponse(
                status: StatusCode::InternalServerError,
                error_message: "There was a problem with the database."
            );
        }

        return new JsonResponse(status:StatusCode::OK);
    }

    #[Route(Method::POST, '{key}/remove')]
    #[Route(Method::DELETE, '{key}/remove')]
    public function DeleteProfile() : Response {
        if (!$this->UserHasPermission("manageoutboundemailprofiles")) {
            return new Response(
                status: StatusCode::NotFound
            );
        }

        $profile = OutboundEmailProfile::LoadFromKey($this->request->Args['key']);

        if ($profile !== null && !$profile->Delete()) {
            return new JsonResponse(
                status: StatusCode::InternalServerError,
                error_message: "There was a problem with the database."
            );
        }

        return new JsonResponse(status:StatusCode::OK);
    }

    #[Route(Method::GET, '{key}/test')]
    public function TestProfile() : Response {
        if (!$this->UserHasPermission("manageoutboundemailprofiles")) {
            return new Response(
                status: StatusCode::NotFound
            );
        }

        if (!isset($this->request->Args['to'])) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'to',
                            'description' => 'Destination address for test email to be sent to',
                            'message' => 'A destination address was not provided.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are missing.'
            );
        }

        $profile = OutboundEmailProfile::LoadFromKey($this->request->Args['key']);

        if ($profile === null) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'key',
                            'description' => 'Unique key for profile',
                            'message' => 'An outbound email profile with the specified key does not exist.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are invalid.'
            );
        }

        $provider = EmailService::GetOutboundEmailProvider($profile);

        if ($provider === null) {
            return new JsonResponse(
                data: [
                    'outboundemailprofile_test_result' => false,
                    'outboundemailprofile_test_errors' => [
                        [
                            'name' => 'type',
                            'description' => 'The type of email server (currently, only SMTP is allowed)',
                            'message' => 'No provider available to handle this outbound email profile\'s type',
                        ],
                    ],
                ],
                status: StatusCode::OK,
            );
        }

        $emailView = new TestEmailView($this->request->Args['key']);
        $message = new EmailMessage($emailView, [$this->request->Args['to']]);

        if (!$provider->SendEmail($message)) {
            return new JsonResponse(
                data: [
                    'outboundemailprofile_test_result' => false,
                    'outboundemailprofile_test_errors' => [
                        [
                            'name' => 'all',
                            'message' => 'The email could not be sent.',
                        ],
                    ],
                ],
                status: StatusCode::OK,
            );
        }

        return new JsonResponse(
            data: [
                'outboundemailprofile_test_result' => true,
            ],
            status: StatusCode::OK,
        );
    }
}