<?php

namespace Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Pivel\Hydro2\Attributes\Entity\Entity;
use Pivel\Hydro2\Services\Entity\EntityRepository;

#[CoversClass(Entity::class)]
#[UsesClass(Entity::class)]
class EntityTest extends TestCase
{
    public function testConstructor()
    {
        $result = new Entity('fakeValue');

        $this->assertInstanceOf(Entity::class, $result);
        $this->assertEquals('fakeValue', $result->CollectionName);
        $this->assertEquals(EntityRepository::class, $result->RepositoryClass);
        $this->assertEquals('primary', $result->PersistenceProfile);
    }
}