<?php

namespace Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Pivel\Hydro2\Exceptions\Database\InvalidUserException;

#[CoversClass(InvalidUserException::class)]
#[UsesClass(InvalidUserException::class)]
class InvalidUserExceptionTest extends TestCase
{
    public function testException()
    {
        $this->expectException(InvalidUserException::class);
        $this->expectExceptionMessage('fakeValue');
        $this->expectExceptionCode(1);

        throw new InvalidUserException('fakeValue', 1);
    }
}