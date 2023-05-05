<?php

namespace Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Pivel\Hydro2\Exceptions\Email\EmailHostNotFoundException;

#[CoversClass(EmailHostNotFoundException::class)]
#[UsesClass(EmailHostNotFoundException::class)]
class EmailHostNotFoundExceptionTest extends TestCase
{
    public function testException()
    {
        $this->expectException(EmailHostNotFoundException::class);
        $this->expectExceptionMessage('fakeValue');
        $this->expectExceptionCode(1);

        throw new EmailHostNotFoundException('fakeValue', 1);
    }
}