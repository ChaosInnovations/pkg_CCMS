<?php

namespace Pivel\Hydro2\Services\Email;

use Pivel\Hydro2\Extensions\Query;
use Pivel\Hydro2\Models\Email\OutboundEmailProfile;
use Pivel\Hydro2\Services\Entity\IEntityRepository;
use Pivel\Hydro2\Services\Entity\IEntityService;

class EmailService
{
    private IEntityService $_entityService;
    private IEntityRepository $_emailProfileRepository;
    /**
     * @var IOutboundEmailProvider[]
     */
    private array $outboundEmailProviders = [];

    public function __construct(
        IEntityService $entityService,
    ) {
        $this->_entityService = $entityService;

        $this->_emailProfileRepository = $entityService->GetRepository(OutboundEmailProfile::class);
    }

    // Singleton pattern
    // TODO use DI pattern
    /**
     * @var IOutboundEmailProvider[]
     */
    public function GetOutboundEmailProviderInstance(string $key) : ?IOutboundEmailProvider {
        if (!isset($this->outboundEmailProviders[$key])) {
            $profiles = $this->_emailProfileRepository->Read((new Query())->Equal('key', $key));
            if (count($profiles) != 1) {
                return null;
            }
            $this->outboundEmailProviders[$key] = self::GetOutboundEmailProvider($profiles[0]);
        }

        if (!($this->outboundEmailProviders[$key] instanceof IOutboundEmailProvider)) {
            return null;
        }

        return $this->outboundEmailProviders[$key];
    }

    // TODO move to manifest
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