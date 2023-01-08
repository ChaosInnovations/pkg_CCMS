<?php

namespace Package\Pivel\Hydro2\Identity\Services;

use Package\Pivel\Hydro2\Core\Models\Request;
use Package\Pivel\Hydro2\Identity\Models\Session;
use Package\Pivel\Hydro2\Identity\Models\User;
use Package\Pivel\Hydro2\Identity\Models\UserRole;

class IdentityService
{
    private static ?User $requestUser = null;
    public static function GetRequestUser(Request $request) : User {
        if (self::$requestUser === null) {
            $random_id = $request->getCookie('s_rid');
            self::$requestUser = Session::LoadFromRandomId($random_id)->GetUser()??(new User(name:'Site Visitor',role:(new UserRole(name:'Site Visitor'))));
        }

        return self::$requestUser;
    }

    // TODO get this from some kind of settings/configuration
    public static function GetDefaultUserRole() : UserRole {
        return UserRole::LoadFromId(1)??(new UserRole('Default','Default Role'));
    }

    public static function GetEmailVerificationUrl(Request $request, User $user, bool $regenerate=false) {
        if ($regenerate) {
            $user->GenerateEmailVerificationToken();
        }
        $token = $user->GetEmailVerificationToken();
        $url = "{$request->baseUrl}/verifyuseremail/{$user->RandomId}?token={$token}";
        return $url;
    }
}