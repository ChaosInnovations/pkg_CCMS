<?php

namespace Package\Database\Models;

use DateTime;
use DateTimeZone;
use Package\Database\Extensions\TableColumn;

class TestObject extends BaseObject {
    #[TableColumn('object_id', autoIncrement:true)]
    public null|int $id = null;
    #[TableColumn('object_inserted')]
    public null|DateTime $insertedTime = null;
    #[TableColumn('object_updated')]
    public null|DateTime $updatedTime = null;
    #[TableColumn('object_name')]
    public string $name;
    #[TableColumn('object_int')]
    public int $int;
    #[TableColumn('object_float')]
    public float $float;
    #[TableColumn('object_bool')]
    public bool $bool;

    public bool $isNew = false;

    public function __construct(string $name, int $int, float $float, bool $bool)
    {
        $this->name = $name;
        $this->int = $int;
        $this->float = $float;
        $this->bool = $bool;

        $this->isNew = false;

        parent::__construct();
    }

    public static function LoadFromId(int $id) : TestObject|null {
        // 1. need to run a query like:
        //     SELECT * FROM [tablename] WHERE [idcolumnname] = [id];
        //     (until easy-to-use querying is implemented, can just use
        //      self::table->dbi->query($query, $params) to run the query
        //      directly)
        // 2. check that there is a single result
        // 3. 'cast' result to an instance of TestObject
        // 4. return instance

        return new TestObject('1',1,1.1,false);
    }

    public static function GetAll() : array {
        // 1. need to run a query like:
        //     SELECT * FROM [tablename];
        //     (until easy-to-use querying is implemented, can just use
        //      self::table->dbi->query($query, $params) to run the query
        //      directly)
        $table = self::getTable();
        //var_dump($table->tableName);
        //var_dump($table->Exists());
        // 3. 'cast' each result to an instance of TestObject
        // 4. return array of instances

        $testObjects = [];
        $testObjects[] = new TestObject('1',1,1.1,false);
        return $testObjects;
    }

    public function Save() : void {
        $now = new DateTime(timezone:new DateTimeZone('UTC'));
        $this->updatedTime = $now;
        if ($this->isNew) {
            $this->insertedTime = $now;
        }

        $this->UpdateOrCreateEntry();
    }

    public function Delete() : void {
        $this->DeleteEntry();
    }
}