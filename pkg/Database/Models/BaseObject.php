<?php

namespace Package\Database\Models;

use Package\Database\Controllers\IDatabaseProvider;
use Package\Database\Services\DatabaseService;

class BaseObject
{
    protected DatabaseService $db; 

    public function __construct()
    {
        $db = DatabaseService::Instance();
    }

    protected function UpdateOrCreateEntry() : bool {
        return false;
    }

    protected function LoadEntry() : bool {
        return false;
    }

    protected function DeleteEntry() : bool {
        return false;
    }
    
    // functions for creating database table if it doesn't yet exist, and
    // verifying that the database table matches the schema of this object.
    // - maybe only if we encounter an error that suggests the table doesn't exist.
    protected function VerifyTable() : bool {
        return false;
    }

    protected function CreateTable() : bool {
        return false;
    }
    
    // functions for database table migrations between versions?
}