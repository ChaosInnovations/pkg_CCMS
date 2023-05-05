<?php

namespace Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Pivel\Hydro2\Attributes\Entity\EntityPrimaryKey;

#[CoversClass(EntityPrimaryKey::class)]
#[UsesClass(EntityPrimaryKey::class)]
class EntityPrimaryKeyTest extends TestCase
{
    public function testConstructor()
    {
        $result = new EntityPrimaryKey();

        $this->assertInstanceOf(EntityPrimaryKey::class, $result);
    }
}