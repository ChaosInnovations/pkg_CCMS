<?php

namespace Package\Pivel\Hydro2\Identity\Services;

use DateTime;
use DateTimeZone;
use Package\Pivel\Hydro2\Core\Models\Request;
use Package\Pivel\Hydro2\Email\Models\EmailAddress;
use Package\Pivel\Hydro2\Email\Models\EmailMessage;
use Package\Pivel\Hydro2\Email\Services\EmailService;
use Package\Pivel\Hydro2\Email\Views\BaseEmailView;
use Package\Pivel\Hydro2\Identity\Models\PasswordResetToken;
use Package\Pivel\Hydro2\Identity\Models\Permissions;
use Package\Pivel\Hydro2\Identity\Models\Session;
use Package\Pivel\Hydro2\Identity\Models\User;
use Package\Pivel\Hydro2\Identity\Models\UserRole;

class IdentityService
{
    private static ?User $requestUser = null;
    public static function GetRequestUser(Request $request) : User {
        if (self::$requestUser === null) {
            self::$requestUser = new User(name:'Site Visitor',role:(new UserRole(name:'Site Visitor')));
            $random_id = $request->getCookie('s_rid');
            // TODO Validate that session details match request
            $session = Session::LoadFromRandomId($random_id);
            if ($session != null) {
                self::$requestUser = $session->GetUser();
                $session->LastAccessTime = new DateTime(timezone:new DateTimeZone('UTC'));
                $session->LastIP = $request->getClientAddress();
                $session->Save();
            }
        }

        return self::$requestUser;
    }

    // TODO get this from some kind of settings/configuration
    public static function GetDefaultUserRole() : UserRole {
        $role = UserRole::LoadFromId(1);
        if ($role == null) {
            $role = new UserRole('Default','Default Role');
            $role->Id = 1;
            $role->AddPermissionString(Permissions::ViewUsers->value);
            $role->AddPermissionString(Permissions::ManageUsers->value);
            $role->AddPermissionString(Permissions::CreateUsers->value);
            $role->AddPermissionString(Permissions::PasswordReset->value);
            $role->AddPermissionString(Permissions::CreateUserRoles->value);
            $role->AddPermissionString(Permissions::ManageUserRoles->value);
            $role->AddPermissionString(Permissions::ViewUserSessions->value);
            $role->AddPermissionString(Permissions::EndUserSessions->value);
            $role->Save();
        }
        return $role;
    }

    public static function GetEmailVerificationUrl(Request $request, User $user, bool $regenerate=false) {
        if ($regenerate) {
            $user->GenerateEmailVerificationToken();
        }
        $token = $user->GetEmailVerificationToken();
        $url = "{$request->baseUrl}/verifyuseremail/{$user->RandomId}?token={$token}";
        return $url;
    }

    public static function SendEmailToUser(User $user, BaseEmailView $emailView) : bool {
        $emailProfileProvider = EmailService::GetOutboundEmailProviderInstance('noreply');
        if ($emailProfileProvider === null) {
            return false;
        }

        $emailMessage = new EmailMessage($emailView, [new EmailAddress($user->Email, $user->Name)]);
        return $emailProfileProvider->SendEmail($emailMessage);
    }

    public static function IsPasswordResetTokenValid(string $token, User $user) : bool {
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

        return true;
    }

    public static function GetPasswordResetUrl(Request $request, User $user, PasswordResetToken $token) {
        $url = "{$request->baseUrl}/resetpassword/{$user->RandomId}?token={$token->ResetToken}";
        return $url;
    }
}