<?php

namespace Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Pivel\Hydro2\Attributes\Entity\EntityField;

#[CoversClass(EntityField::class)]
#[UsesClass(EntityField::class)]
class EntityFieldTest extends TestCase
{
    public function testConstructor()
    {
        $result = new EntityField();

        $this->assertInstanceOf(EntityField::class, $result);
        $this->assertEquals(null, $result->FieldName);
        $this->assertEquals(null, $result->FieldType);
        $this->assertEquals(false, $result->IsNullable);
        $this->assertEquals(false, $result->AutoIncrement);
    }
}