<?php

namespace Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Pivel\Hydro2\Exceptions\Email\NotAuthenticatedException;

#[CoversClass(NotAuthenticatedException::class)]
#[UsesClass(NotAuthenticatedException::class)]
class NotAuthenticatedExceptionTest extends TestCase
{
    public function testException()
    {
        $this->expectException(NotAuthenticatedException::class);
        $this->expectExceptionMessage('fakeValue');
        $this->expectExceptionCode(1);

        throw new NotAuthenticatedException('fakeValue', 1);
    }
}