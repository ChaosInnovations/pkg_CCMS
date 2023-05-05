<?php

namespace Pivel\Hydro2\Services;

use Pivel\Hydro2\Models\EnvironmentType;

interface IEnvironmentService
{
    /**
     * Returns the value of the requested environment variable, or the provided default value if not set.
     */
    public function GetEnvironmentVariable(string $key, string $defaultValue=''): string;
    /**
     * @return string[] An associative array of all environment variables.
     */
    public function GetAllEnvironmentVariables(): array;
    /**
     * Detects the type of environment based on the HYDRO2_ENV environment variable.
     * If set to DEV or DEVELOPMENT, environment is Development.
     * If set to STAGING, environment is Staging.
     * If unset or set to PROD or PRODUCTION, environment is Production.
     * @return EnvironmentType
     */
    public function GetEnvironmentType(): EnvironmentType;
}