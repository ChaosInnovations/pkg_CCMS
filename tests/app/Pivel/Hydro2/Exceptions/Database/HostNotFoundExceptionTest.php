<?php

namespace Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Pivel\Hydro2\Exceptions\Database\HostNotFoundException;

#[CoversClass(HostNotFoundException::class)]
#[UsesClass(HostNotFoundException::class)]
class HostNotFoundExceptionTest extends TestCase
{
    public function testException()
    {
        $this->expectException(HostNotFoundException::class);
        $this->expectExceptionMessage('fakeValue');
        $this->expectExceptionCode(1);

        throw new HostNotFoundException('fakeValue', 1);
    }
}