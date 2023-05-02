<?php

namespace Mocks\Services;

use Pivel\Hydro2\Services\ILoggerService;

class MockLoggerService implements ILoggerService
{
    public function Info(string $package, string $message): void
    {
        
    }

    public function Warn(string $package, string $message): void
    {
        
    }

    public function Error(string $package, string $message): void
    {
        
    }

    public function Debug(string $package, string $message): void
    {
        
    }
}