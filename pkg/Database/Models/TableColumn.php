<?php

namespace Package\Database\Models;

use Package\Database\Controllers\IDatabaseProvider;
use Package\Database\Services\DatabaseService;

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
        public null|bool $foreignKeyTable = null,
        public null|bool $foreignKeyColumnName = null,
        public ReferenceBehaviour $foreignKeyOnUpdate = ReferenceBehaviour::RESTRICT,
        public ReferenceBehaviour $foreignKeyOnDelete = ReferenceBehaviour::RESTRICT,
        ) {
    }
}