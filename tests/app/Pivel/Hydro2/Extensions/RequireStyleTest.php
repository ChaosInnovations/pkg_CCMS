<?php

namespace Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Pivel\Hydro2\Extensions\RequireStyle;

#[CoversClass(RequireStyle::class)]
#[UsesClass(RequireStyle::class)]
class RequireStyleTest extends TestCase
{
    public function testConstructor()
    {
        $result = new RequireStyle('fakeValue', false);

        $this->assertInstanceOf(RequireStyle::class, $result);
        $this->assertEquals('fakeValue', $result->Path);
        $this->assertEquals(false, $result->Inline);
    }
}