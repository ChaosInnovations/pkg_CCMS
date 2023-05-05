<?php

namespace Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Pivel\Hydro2\Exceptions\Database\TableNotFoundException;

#[CoversClass(TableNotFoundException::class)]
#[UsesClass(TableNotFoundException::class)]
class TableNotFoundExceptionTest extends TestCase
{
    public function testException()
    {
        $this->expectException(TableNotFoundException::class);
        $this->expectExceptionMessage('fakeValue');
        $this->expectExceptionCode(1);

        throw new TableNotFoundException('fakeValue', 1);
    }
}