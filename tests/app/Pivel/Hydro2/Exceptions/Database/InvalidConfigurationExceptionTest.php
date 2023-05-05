<?php

namespace Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Pivel\Hydro2\Exceptions\Database\InvalidConfigurationException;

#[CoversClass(InvalidConfigurationException::class)]
#[UsesClass(InvalidConfigurationException::class)]
class InvalidConfigurationExceptionTest extends TestCase
{
    public function testException()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('fakeValue');
        $this->expectExceptionCode(1);

        throw new InvalidConfigurationException('fakeValue', 1);
    }
}