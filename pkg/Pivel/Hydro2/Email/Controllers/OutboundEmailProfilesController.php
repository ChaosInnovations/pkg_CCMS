<?php

namespace Package\Pivel\Hydro2\Email\Controllers;

use Package\Pivel\Hydro2\Core\Controllers\BaseController;
use Package\Pivel\Hydro2\Core\Extensions\Route;
use Package\Pivel\Hydro2\Core\Extensions\RoutePrefix;
use Package\Pivel\Hydro2\Core\Models\HTTP\Method;
use Package\Pivel\Hydro2\Core\Models\HTTP\StatusCode;
use Package\Pivel\Hydro2\Core\Models\JsonResponse;
use Package\Pivel\Hydro2\Core\Models\Response;
use Package\Pivel\Hydro2\Email\Models\EmailAddress;
use Package\Pivel\Hydro2\Email\Models\EmailMessage;
use Package\Pivel\Hydro2\Email\Models\OutboundEmailProfile;
use Package\Pivel\Hydro2\Email\Services\EmailService;
use Package\Pivel\Hydro2\Email\Views\TestEmailView;

#[RoutePrefix('api/hydro2/email/outboundprofiles')]
class OutboundEmailProfilesController extends BaseController
{
    // TODO replace with real permission check
    private function UserHasPermission(string $permission) : bool {
        return true;
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

        /**
         * @var OutboundEmailProfile[]
         */
        $profiles = OutboundEmailProfile::GetAll();
        $serializedProfiles = [];
        foreach ($profiles as $profile) {
            $serializedProfiles[] = [
                'key' => $profile->Key,
                'label' => $profile->Label,
                'type' => $profile->Type,
                'sender' => $profile->GetSender()->__toString(),
                'require_auth' => $profile->RequireAuth,
                'username' => $profile->Username,
                // don't provide password
                'host' => $profile->Host,
                'port' => $profile->Port,
                'secure' => $profile->Secure,
            ];
        }

        return new JsonResponse(
            data: [
                'outboundemailprofiles' => $serializedProfiles,
            ],
            status: StatusCode::OK,
        );
    }

    #[Route(Method::GET, 'getproviders')]
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
        
        $secure = $this->request->Args['secure']??OutboundEmailProfile::SECURE_NONE;
        if (!in_array($secure, [
            OutboundEmailProfile::SECURE_NONE,
            OutboundEmailProfile::SECURE_SSL,
            OutboundEmailProfile::SECURE_TLS_AUTO,
            OutboundEmailProfile::SECURE_TLS_REQUIRE,
        ])) {
            $secure = OutboundEmailProfile::SECURE_NONE;
        }

        $profile = new OutboundEmailProfile(
            key: $this->request->Args['key'],
            label: $this->request->Args['label']??'Unnamed Email Profile',
            type: $this->request->Args['type']??'smtp',
            sender: new EmailAddress(
                $this->request->Args['sender_address']??'',
                $this->request->Args['sender_name']??''
            ),
            requireAuth: $this->request->Args['require_auth']??false,
            username: $this->request->Args['username']??null,
            password: $this->request->Args['password']??null,
            host: $this->request->Args['host']??'localhost',
            port: $this->request->Args['port']??465,
            secure: $secure,
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
                'sender' => $profile->GetSender()->__toString(),
                'require_auth' => $profile->RequireAuth,
                'username' => $profile->Username,
                // don't provide password
                'host' => $profile->Host,
                'port' => $profile->Port,
                'secure' => $profile->Secure,
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

        $secure = $this->request->Args['secure']??OutboundEmailProfile::SECURE_NONE;
        if (!in_array($secure, [
            OutboundEmailProfile::SECURE_NONE,
            OutboundEmailProfile::SECURE_SSL,
            OutboundEmailProfile::SECURE_TLS_AUTO,
            OutboundEmailProfile::SECURE_TLS_REQUIRE,
        ])) {
            $secure = OutboundEmailProfile::SECURE_NONE;
        }
        
        $profile->Label = $this->request->Args['label']??'Unnamed Email Profile';
        $profile->Type = $this->request->Args['type']??'smtp';
        $profile->SenderAddress = $this->request->Args['sender_address']??'';
        $profile->SenderName = $this->request->Args['sender_name']??'';
        $profile->RequireAuth = $this->request->Args['require_auth']??false;
        $profile->Username = $this->request->Args['username']??null;
        // if new password not provided, keep the same one.
        $profile->Password = $this->request->Args['password']??$profile->Password;
        $profile->Host = $this->request->Args['host']??'localhost';
        $profile->Port = $this->request->Args['port']??465;
        $profile->Secure = $secure;

        if (!$profile->Save()) {
            return new JsonResponse(
                status: StatusCode::InternalServerError,
                error_message: "There was a problem with the database."
            );
        }

        return new JsonResponse(status:StatusCode::OK);
    }

    #[Route(Method::POST, '{key}/remove')]
    #[Route(Method::DELETE, '{key}')]
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
        $message = new EmailMessage($emailView, [new EmailAddress($this->request->Args['to'])]);

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