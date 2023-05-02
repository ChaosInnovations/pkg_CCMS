<?php

namespace Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Pivel\Hydro2\Attributes\Entity\ForeignEntityManyToOne;
use Pivel\Hydro2\Models\Database\ReferenceBehaviour;

#[CoversClass(ForeignEntityManyToOne::class)]
#[UsesClass(ForeignEntityManyToOne::class)]
class ForeignEntityManyToOneTest extends TestCase
{
    public function testConstructor()
    {
        $result = new ForeignEntityManyToOne();

        $this->assertInstanceOf(ForeignEntityManyToOne::class, $result);
        $this->assertEquals(null, $result->OtherEntityClass);
        $this->assertEquals(null, $result->OtherEntityFieldName);
        $this->assertEquals(ReferenceBehaviour::CASCADE, $result->OnUpdate);
        $this->assertEquals(ReferenceBehaviour::RESTRICT, $result->OnDelete);
    }
}