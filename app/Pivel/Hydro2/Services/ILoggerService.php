<?php

namespace Pivel\Hydro2\Services;

interface ILoggerService
{
    public function Info(string $package, string $message) : void;
    public function Warn(string $package, string $message) : void;
    public function Error(string $package, string $message) : void;
    public function Debug(string $package, string $message) : void;
}