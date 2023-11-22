<?php

namespace Pivel\Hydro2\Services\Entity;

use DateTime;
use Pivel\Hydro2\Attributes\Entity\ForeignEntityOneToMany;
use Pivel\Hydro2\Exceptions\Database\TableNotFoundException;
use Pivel\Hydro2\Extensions\Query;
use Pivel\Hydro2\Models\Database\Type;
use Pivel\Hydro2\Models\EntityDefinition;
use Pivel\Hydro2\Services\ILoggerService;
use ReflectionClass;
use ReflectionProperty;
use TypeError;

/**
 * @template TEntity
 */
class EntityRepository implements IEntityRepository
{
    private IEntityService $_entityService;
    private IEntityPersistenceProvider $_provider;
    private ILoggerService $_logger;
    /**
     * @var class-string<TEntity>
     */
    private string $entityClass;
    /**
     * @var EntityDefinition<TEntity>
     */
    private EntityDefinition $definition;

    public function __construct(IEntityService $entityService, IEntityPersistenceProvider $provider, ILoggerService $logger, string $entityClass)
    {
        $this->_entityService = $entityService;
        $this->_provider = $provider;
        $this->_logger = $logger;
        $this->entityClass = $entityClass;
        $this->definition = new EntityDefinition($entityClass);
    }

    public function Read(?Query $query = null) : array
    {
        try {
            $results = $this->_provider->Select($this->definition, $query);
        } catch (TableNotFoundException) {
            $this->_logger->Warn('Pivel/Hydro2', "Definition '{$this->definition->GetName()}' not found.");
            $this->_logger->Info('Pivel/Hydro2', "Creating definition '{$this->definition->GetName()}'...");
            $created = $this->_provider->CreateCollectionIfNotExists($this->definition);
            if (!$created) {
                $this->_logger->Error('Pivel/Hydro2', "Failed to create definition '{$this->definition->GetName()}'.");
                return [];
            }
            $this->_logger->Info('Pivel/Hydro2', "Successfully created '{$this->definition->GetName()}'.");
            $results = $this->_provider->Select($this->definition, $query);
        }

        // Cast results
        /** @var TEntity[] */
        $entities = [];
        $count = 0;
        foreach ($results as $result) {
            $entities[] = $this->EntityFromArray($result);
            $count++;
        }

        //$this->_logger->Debug('Pivel/Hydro2', "Found {$count} entries from collection '{$this->definition->GetName()}' that match the query.");
        return $entities;
    }

    public function ReadById(mixed $id) : ?object
    {
        $pkField = $this->definition->GetPrimaryKeyField();
        if ($pkField === null) {
            return null;
        }

        $results = $this->Read((new Query)->Equal($pkField->FieldName, $id));
        if (count($results) != 1) {
            return null;
        }
        return $results[0];
    }

    public function Count(?Query $query = null) : int
    {
        try {
            $result = $this->_provider->Count($this->definition, $query);
        } catch (TableNotFoundException) {
            $this->_logger->Warn('Pivel/Hydro2', "definition '{$this->definition->GetName()}' not found.");
            $this->_logger->Info('Pivel/Hydro2', "Creating definition '{$this->definition->GetName()}'...");
            $created = $this->_provider->CreateCollectionIfNotExists($this->definition);
            if (!$created) {
                $this->_logger->Error('Pivel/Hydro2', "Failed to create definition '{$this->definition->GetName()}'.");
                return 0;
            }
            $this->_logger->Info('Pivel/Hydro2', "Successfully created '{$this->definition->GetName()}'.");
            $result = $this->_provider->Count($this->definition, $query);
        }

        return $result;
    }

    public function Create(object &$entity) : bool
    {
        if (!($entity instanceof ($this->entityClass))) {
            throw new TypeError("Expected object of type {$this->entityClass}.");
        }

        $values = $this->ArrayFromEntity($entity);

        try {
            $pk = $this->_provider->Insert($this->definition, $values);
        } catch (TableNotFoundException) {
            $this->_logger->Warn('Pivel/Hydro2', "definition '{$this->definition->GetName()}' not found.");
            $this->_logger->Info('Pivel/Hydro2', "Creating definition '{$this->definition->GetName()}'...");
            $created = $this->_provider->CreateCollectionIfNotExists($this->definition);
            if (!$created) {
                $this->_logger->Error('Pivel/Hydro2', "Failed to create definition '{$this->definition->GetName()}'.");
                return false;
            }
            $this->_logger->Info('Pivel/Hydro2', "Successfully created '{$this->definition->GetName()}'.");
            $pk = $this->_provider->Insert($this->definition, $values);
        }

        if ($pk !== null) {
            $this->SetEntityPrimaryKey($entity, $pk);
            $this->SetEntityCollections($entity);
        }

        return true;
    }

    public function Update(object &$entity) : bool
    {
        if (!($entity instanceof ($this->entityClass))) {
            $this->_logger->Warn("Pivel/Hydro2", "Expected object of type {$this->entityClass}.");
            throw new TypeError("Expected object of type {$this->entityClass}.");
        }

        $values = $this->ArrayFromEntity($entity);

        try {
            $pk = $this->_provider->InsertOrUpdate($this->definition, $values);
        } catch (TableNotFoundException) {
            $this->_logger->Warn('Pivel/Hydro2', "definition '{$this->definition->GetName()}' not found.");
            $this->_logger->Info('Pivel/Hydro2', "Creating definition '{$this->definition->GetName()}'...");
            $created = $this->_provider->CreateCollectionIfNotExists($this->definition);
            if (!$created) {
                $this->_logger->Error('Pivel/Hydro2', "Failed to create definition '{$this->definition->GetName()}'.");
                return false;
            }
            $this->_logger->Info('Pivel/Hydro2', "Successfully created '{$this->definition->GetName()}'.");
            $pk = $this->_provider->InsertOrUpdate($this->definition, $values);
        }

        if ($pk !== null) {
            $this->SetEntityPrimaryKey($entity, $pk);
            $this->SetEntityCollections($entity);
        }

        return true;
    }

    public function Delete(object $entity) : bool
    {
        if (!($entity instanceof ($this->entityClass))) {
            throw new TypeError("Expected object of type {$this->entityClass}.");
        }

        $pkField = $this->definition->GetPrimaryKeyField();

        if ($pkField === null) {
            return false;
        }

        try {
            $affectedRows = $this->_provider->Delete($this->definition, (new Query())->Equal($pkField->FieldName, $this->GetEntityPrimaryKey($entity)));
        } catch (TableNotFoundException) {
            $this->_logger->Warn('Pivel/Hydro2', "definition '{$this->definition->GetName()}' not found.");
            $this->_logger->Info('Pivel/Hydro2', "Creating definition '{$this->definition->GetName()}'...");
            $created = $this->_provider->CreateCollectionIfNotExists($this->definition);
            if (!$created) {
                $this->_logger->Error('Pivel/Hydro2', "Failed to create definition '{$this->definition->GetName()}'.");
                return 0;
            }
            $this->_logger->Info('Pivel/Hydro2', "Successfully created '{$this->definition->GetName()}'.");
            $affectedRows = $this->_provider->Delete($this->definition, (new Query())->Equal($pkField->FieldName, $this->GetEntityPrimaryKey($entity)));
        }

        return $affectedRows == 1;
    }

    

    /**
     * @param mixed[] $values The data to cast to an entity.
     * @return TEntity The entity.
     */
    private function EntityFromArray(array $values) : object {
        /** @var TEntity */
        $entity = new $this->entityClass();
        foreach ($this->definition as $field) {
            if (!isset($values[$field->FieldName])) {
                continue;
            }

            $value = $values[$field->FieldName];

            // TODO type conversion
            if ($field->FieldType == Type::DATETIME) {
                if ($field->IsNullable && $value == null) {
                    $value = null;
                } else {
                    $value = new DateTime($value.'+00:00');
                }
            }

            if (!$field->IsForeignKey) {
                $field->Property->setValue($entity, $value);
                continue;
            }

            $r = $this->_entityService->GetRepository($field->ForeignKeyClassName);
            $foreignEntities = $r->Read((new Query)->Equal($field->foreignKeyCollectionFieldName, $value));
            if (count($foreignEntities) == 1) {
                $field->Property->setValue($entity, $foreignEntities[0]);
            }
        }

        $this->SetEntityCollections($entity);

        return $entity;
    }

    /**
     * @param TEntity $entity The entity to be converted to an array.
     * @return mixed[] The entity's field values as an array
     */
    private function ArrayFromEntity(object $entity) : array {
        $values = [];
        foreach ($this->definition as $field) {
            $value = $field->Property->getValue($entity);

            // TODO type conversion
            if ($field->FieldType == Type::DATETIME) {
                /** @var DateTime $value */
                if ($field->IsNullable && $value == null) {
                    $value = null;
                } else {
                    $value = $value->format('c');
                }
            }

            if (!$field->IsForeignKey) {
                $values[$field->FieldName] = $value;
                continue;
            }

            $fkPkField = (new EntityDefinition($field->ForeignKeyClassName))->GetPrimaryKeyField();
            $values[$field->FieldName] = $value===null?null:$fkPkField->Property->getValue($value);
        }

        return $values;
    }

    /**
     * @param TEntity &$entity The entity whose primary key field is to be set
     * @param mixed $value The primary key value to set
     */
    private function SetEntityPrimaryKey(object &$entity, mixed $value) : void
    {
        if ($this->definition->GetPrimaryKeyField() == null) {
            return;
        }

        $this->definition->GetPrimaryKeyField()->Property->setValue($entity, $value);
    }

    /**
     * @param TEntity $entity
     * @return mixed The primary key's value.
     */
    private function GetEntityPrimaryKey(object $entity) : mixed
    {
        if ($this->definition->GetPrimaryKeyField() == null) {
            return null;
        }

        return $this->definition->GetPrimaryKeyField()->Property->getValue($entity);
    }

    /**
     * @param TEntity &$entity
     */
    private function SetEntityCollections(object &$entity) : void
    {
        $rc = new ReflectionClass($entity);
        foreach ($rc->getProperties() as $property) {
            $attrs = $property->getAttributes(ForeignEntityOneToMany::class);
            if (count($attrs) != 1) {
                continue;
            }

            $attr = $attrs[0]->newInstance();

            if ($attr->OtherEntityFieldName == null) {
                $d = new EntityDefinition($attr->OtherEntityClass);
                foreach ($d as $field) {
                    if (!$field->IsForeignKey) {
                        continue;
                    }

                    if ($field->ForeignKeyClassName != $this->entityClass) {
                        continue;
                    }

                    $attr->OtherEntityFieldName = $field->FieldName;
                    break;
                }

                if ($attr->OtherEntityFieldName == null) {
                    // Couldn't determine the inverse field name.
                    continue;
                }
            }

            $collection = new EntityCollection(
                $this->_entityService->GetRepository($attr->OtherEntityClass),
                (new Query)->Equal($attr->OtherEntityFieldName, $this->GetEntityPrimaryKey($entity)),
            );

            $property->setValue($entity, $collection);
        }
    }
}