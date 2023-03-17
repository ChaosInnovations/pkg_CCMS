<?php

namespace Pivel\Hydro2\Models\Database;

class TableColumn
{
    public function __construct(
        public string $columnName,
        public string $propertyName,
        public Type $columnType,
        public string $propertyType,
        public bool $propertyTypeNullable,
        public bool $autoIncrement,
        public bool $primaryKey,
        public bool $foreignKey = false,
        public ?string $foreignKeyTable = null,
        public ?string $foreignKeyColumnName = null,
        public ReferenceBehaviour $foreignKeyOnUpdate = ReferenceBehaviour::RESTRICT,
        public ReferenceBehaviour $foreignKeyOnDelete = ReferenceBehaviour::RESTRICT,
        ) {
    }
}