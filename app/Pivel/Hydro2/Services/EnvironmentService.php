<?php

namespace Pivel\Hydro2\Services;

use Pivel\Hydro2\Models\EnvironmentType;

class EnvironmentService implements IEnvironmentService
{
    public function GetEnvironmentVariable(string $key, string $defaultValue=''): string
    {
        $value = getenv($key);

        if ($value === false) {
            return $defaultValue;
        }

        return $value;
    }

    public function GetAllEnvironmentVariables(): array
    {
        return getenv();
    }

    public function GetEnvironmentType(): EnvironmentType
    {
        $typeStr = strtoupper($this->GetEnvironmentVariable('HYDRO2_ENV', 'PRODUCTION'));

        $type = EnvironmentType::Production;
        switch ($typeStr) {
            case 'DEV':
            case 'DEVELOPMENT':
                $type = EnvironmentType::Development;
                break;
            case 'STAGING':
                $type = EnvironmentType::Staging;
                break;
            case 'PROD':
            case 'PRODUCTION':
            default:
                $type = EnvironmentType::Production;
                break;
        }

        return $type;
    }
}