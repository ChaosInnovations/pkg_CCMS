<?php

namespace Package\Pivel\Hydro2\Database\Models;

use Package\Pivel\Hydro2\Database\Controllers\IDatabaseProvider;
use Package\Pivel\Hydro2\Database\Services\DatabaseService;

class TableColumn
{
    public function __construct(
        public string $columnName,
        public string $propertyName,
        public Type $columnType,
        public string $propertyType,
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