<?php

namespace Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Pivel\Hydro2\Extensions\RequireScript;

#[CoversClass(RequireScript::class)]
#[UsesClass(RequireScript::class)]
class RequireScriptTest extends TestCase
{
    public function testConstructor()
    {
        $result = new RequireScript('fakeValue', false);

        $this->assertInstanceOf(RequireScript::class, $result);
        $this->assertEquals('fakeValue', $result->Path);
        $this->assertEquals(false, $result->Inline);
    }
}