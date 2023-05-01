<?php

namespace Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Pivel\Hydro2\Hydro2;

#[CoversClass(Hydro2::class)]
#[UsesClass(Hydro2::class)]
class Hydro2Test extends TestCase
{
    public function testHydro2BuilderShouldReturnHydro2Instance()
    {
        $result = Hydro2::CreateHydro2App();
        $this->assertInstanceOf(Hydro2::class, $result);
    }

    public function testRegisterTransientToHydro2InstanceThenInstantiate()
    {
        $result = Hydro2::CreateHydro2App();
        $result->RegisterTransient(MockTransientClass::class);
        $resolvedDependency = $result->ResolveDependency(MockTransientClass::class, [1]);
        $this->assertInstanceOf(MockTransientClass::class, $resolvedDependency);
        $this->assertInstanceOf(Hydro2::class, $resolvedDependency->app);
        $this->assertEquals(1, $resolvedDependency->extraArg);
    }
}

class MockTransientClass
{
    public Hydro2 $app;
    public int $extraArg;
    public function __construct(Hydro2 $app, int $extraArg)
    {
        $this->app = $app;
        $this->extraArg = $extraArg;
    }
}