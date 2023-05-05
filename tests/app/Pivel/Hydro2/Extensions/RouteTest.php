<?php

namespace Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Pivel\Hydro2\Extensions\Route;
use Pivel\Hydro2\Models\HTTP\Method;

#[CoversClass(Route::class)]
#[UsesClass(Route::class)]
class RouteTest extends TestCase
{
    public function testConstructor()
    {
        $result = new Route(Method::GET, 'fakeValue', 0);

        $this->assertInstanceOf(Route::class, $result);
        $this->assertEquals(Method::GET, $result->method);
        $this->assertEquals('fakeValue', $result->path);
        $this->assertEquals(0, $result->order);
    }
}