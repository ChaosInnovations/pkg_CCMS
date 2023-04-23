<?php

namespace Pivel\Hydro2\Services\Email;

use Pivel\Hydro2\Models\Email\OutboundEmailProfile;

class EmailService
{
    // Singleton pattern
    // TODO use DI pattern
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

    public function GetOutboundEmailProvider(OutboundEmailProfile $profile) : ?IOutboundEmailProvider {
        if (!isset(self::$emailProviders[$profile->Type])) {
            return null;
        }

        $providerName = self::$emailProviders[$profile->Type];
        return new $providerName($profile);
    }

    /**
     * @return string[]
     */
    public function GetAvailableProviders() : array {
        return array_keys(self::$emailProviders);
    }
}