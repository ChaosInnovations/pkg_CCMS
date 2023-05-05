<?php

namespace Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Pivel\Hydro2\Extensions\RoutePrefix;
use Pivel\Hydro2\Models\HTTP\Method;

#[CoversClass(RoutePrefix::class)]
#[UsesClass(RoutePrefix::class)]
class RoutePrefixTest extends TestCase
{
    public function testConstructor()
    {
        $result = new RoutePrefix('fakeValue');

        $this->assertInstanceOf(RoutePrefix::class, $result);
        $this->assertEquals('fakeValue', $result->pathPrefix);
    }
}