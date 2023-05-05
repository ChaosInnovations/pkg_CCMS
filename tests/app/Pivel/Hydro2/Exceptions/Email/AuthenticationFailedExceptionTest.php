<?php

namespace Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Pivel\Hydro2\Exceptions\Email\AuthenticationFailedException;

#[CoversClass(AuthenticationFailedException::class)]
#[UsesClass(AuthenticationFailedException::class)]
class AuthenticationFailedExceptionTest extends TestCase
{
    public function testException()
    {
        $this->expectException(AuthenticationFailedException::class);
        $this->expectExceptionMessage('fakeValue');
        $this->expectExceptionCode(1);

        throw new AuthenticationFailedException('fakeValue', 1);
    }
}