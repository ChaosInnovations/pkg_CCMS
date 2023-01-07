<?php

namespace Package\Pivel\Hydro2\Email\Services;

use Package\Pivel\Hydro2\Email\Models\OutboundEmailProfile;
use Package\Pivel\Hydro2\Email\Services\IOutboundEmailProvider;

class EmailService
{
    // Singleton pattern
    /**
     * @var IOutboundEmailProvider[]
     */
    private static $outboundEmailProvider = [];
    public static function GetOutboundEmailProviderInstance(string $key) : ?IOutboundEmailProvider {
        if (!isset(self::$outboundEmailProvider[$key])) {
            $profile = OutboundEmailProfile::LoadFromKey($key);
            if ($profile === null) {
                return null;
            }
            self::$outboundEmailProvider[$key] = self::GetOutboundEmailProvider($profile);
        }

        if (!(self::$outboundEmailProvider[$key] instanceof IOutboundEmailProvider)) {
            return null;
        }

        return self::$outboundEmailProvider[$key];
    }

    private static array $emailProviders = [
        'smtp' => SMTPProvider::class,
    ];

    public static function GetOutboundEmailProvider(OutboundEmailProfile $profile) : ?IOutboundEmailProvider {
        if (!isset(self::$emailProviders[$profile->Key])) {
            return null;
        }

        $providerName = self::$emailProviders[$profile->Key];
        return new $providerName($profile);
    }

    /**
     * @return string[]
     */
    public static function GetAvailableProviders() : array {
        return array_keys(self::$emailProviders);
    }
}