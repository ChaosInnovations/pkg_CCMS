<?php

namespace Package\Pivel\Hydro2\Identity\Models;

class Permission
{
    public string $FullKey;

    /** @var string[] $Requires Full Keys of permissions required when this permission is granted */
    public function __construct(
        public string $Vendor,
        public string $Package,
        public string $Key,
        public string $Name,
        public string $Description,
        public array $Requires=[],
    )
    {
        $this->FullKey = strtolower($Vendor . '/' . $Package . '/' . $Key);
    }
}