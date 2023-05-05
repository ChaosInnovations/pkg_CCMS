<?php

namespace Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Pivel\Hydro2\Exceptions\Email\TLSUnavailableException;

#[CoversClass(TLSUnavailableException::class)]
#[UsesClass(TLSUnavailableException::class)]
class TLSUnavailableExceptionTest extends TestCase
{
    public function testException()
    {
        $this->expectException(TLSUnavailableException::class);
        $this->expectExceptionMessage('fakeValue');
        $this->expectExceptionCode(1);

        throw new TLSUnavailableException('fakeValue', 1);
    }
}