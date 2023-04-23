<?php

namespace Pivel\Hydro2\Services;

use Pivel\Hydro2\Hydro2;
use Pivel\Hydro2\Models\Email\EmailAddress;
use Pivel\Hydro2\Models\Email\EmailMessage;
use Pivel\Hydro2\Models\HTTP\Request;
use Pivel\Hydro2\Models\Identity\PasswordResetToken;
use Pivel\Hydro2\Models\Identity\Permission;
use Pivel\Hydro2\Models\Identity\Session;
use Pivel\Hydro2\Models\Identity\User;
use Pivel\Hydro2\Models\Identity\UserRole;
use Pivel\Hydro2\Models\Permissions;
use Pivel\Hydro2\Services\Email\EmailService;
use Pivel\Hydro2\Views\EmailViews\BaseEmailView;

class IdentityService
{
    protected PackageManifestService $_manifestService;
    protected EmailService $_emailService;

    public function __construct(
        PackageManifestService $manifestService,
        EmailService $emailService,
    )
    {
        $this->_manifestService = $manifestService;
        $this->_emailService = $emailService;
    }

    private ?User $requestUser = null;
    public  function GetRequestUser(Request $request) : User {
        if ($this->requestUser === null) {
            $this->requestUser = self::GetDefaultVisitorUser();
            $session = self::GetRequestSession($request);
            if ($session !== false) {
                $user = $session->GetUser();
                if ($user !== null) {
                    $this->requestUser = $user;
                }
            }
        }

        return $this->requestUser;
    }

    private Session|false $requestSession = false;
    public function GetRequestSession(Request $request) : Session|false {
        if ($this->requestSession === false) {
            $this->requestSession = Session::LoadAndValidateFromRequest($request);
        }

        if ($this->requestSession === false) {
            setcookie('sridkey', '', time()-3600, '/');
        }

        return $this->requestSession;
    }

    // TODO get this from some kind of settings/configuration
    public function GetDefaultUserRole() : UserRole {
        $role = UserRole::LoadFromId(1);
        if ($role == null) {
            $role = new UserRole('Default','Default Role');
            $role->Id = 1;
            $role->AddPermission(Permissions::ViewUsers->value);
            $role->AddPermission(Permissions::ManageUsers->value);
            $role->AddPermission(Permissions::CreateUsers->value);
            $role->AddPermission(Permissions::CreateUserRoles->value);
            $role->AddPermission(Permissions::ManageUserRoles->value);
            $role->AddPermission(Permissions::ViewUserSessions->value);
            $role->AddPermission(Permissions::EndUserSessions->value);
            $role->Save();
        }
        return $role;
    }

    public function GetDefaultVisitorUser() {
        return new User(name:'Site Visitor',role:(new UserRole(name:'Site Visitor')));
    }

    public function GetEmailVerificationUrl(Request $request, User $user, bool $regenerate=false) {
        if ($regenerate) {
            $user->GenerateEmailVerificationToken();
        }
        $token = $user->GetEmailVerificationToken();
        echo 'Base URL: "'.$request->baseUrl.'"';
        $url = "{$request->baseUrl}/verifyuseremail/{$user->RandomId}?token={$token}";
        return $url;
    }

    public function SendEmailToUser(User $user, BaseEmailView $emailView) : bool {
        $emailProfileProvider = $this->_emailService->GetOutboundEmailProviderInstance('noreply');
        if ($emailProfileProvider === null) {
            return false;
        }

        $emailMessage = new EmailMessage($emailView, [new EmailAddress($user->Email, $user->Name)]);
        return $emailProfileProvider->SendEmail($emailMessage);
    }

    public function IsPasswordResetTokenValid(string $token, User $user) : bool {
        $token_obj = PasswordResetToken::LoadFromToken($token);
        if ($token_obj === null) {
            return false;
        }

        if (!$token_obj->CompareToken($token)) {
            return false;
        }

        if ($token_obj->UserId !== $user->Id) {
            return false;
        }

        $token_obj->Used = true;
        $token_obj->Save();

        return true;
    }

    public function GetPasswordResetUrl(Request $request, User $user, PasswordResetToken $token) {
        $url = "{$request->baseUrl}/resetpassword/{$user->RandomId}?token={$token->ResetToken}";
        return $url;
    }

    /** @return Permission[] */
    public function GetAvailablePermissions() : array {
        /** @var Permission[] $permissions */
        $permissions = [];
        $packageManifest = $this->_manifestService->GetPackageManifest();
        foreach ($packageManifest as $vendorName => $vendorPackages) {
            foreach ($vendorPackages as $packageName => $package) {
                if (!isset($package['permissions'])) {
                    continue;
                }

                // pivel/hydro2/viewusers

                foreach ($package['permissions'] as $permission) {
                    $permissions[strtolower($vendorName . '/' . $packageName . '/' . $permission['key'])] = new Permission(
                        $vendorName,
                        $packageName,
                        strtolower($permission['key']),
                        $permission['name']??$permission['key'],
                        $permission['description']??$permission['key'],
                        $permission['requires']??[],
                    );
                }
            }
        }

        return $permissions;
    }
}