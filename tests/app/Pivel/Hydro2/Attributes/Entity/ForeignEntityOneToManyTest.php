<?php

namespace Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Pivel\Hydro2\Attributes\Entity\ForeignEntityOneToMany;

#[CoversClass(ForeignEntityOneToMany::class)]
#[UsesClass(ForeignEntityOneToMany::class)]
class ForeignEntityOneToManyTest extends TestCase
{
    public function testConstructor()
    {
        $result = new ForeignEntityOneToMany('fakeValue');

        $this->assertInstanceOf(ForeignEntityOneToMany::class, $result);
        $this->assertEquals('fakeValue', $result->OtherEntityClass);
        $this->assertEquals(null, $result->OtherEntityFieldName);
    }
}