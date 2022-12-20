<?php

namespace Package\Pivel\Hydro2\Database\Models;

use DateTime;
use DateTimeZone;
use Package\Pivel\Hydro2\Database\Extensions\TableColumn;
use Package\Pivel\Hydro2\Database\Extensions\TablePrimaryKey;
use Package\Pivel\Hydro2\Database\Extensions\TableName;
use Package\Pivel\Hydro2\Database\Extensions\Where;

#[TableName('test_object')]
class TestObject extends BaseObject {
    #[TableColumn('id', autoIncrement:true)]
    #[TablePrimaryKey]
    public null|int $id = null;
    public function getId() : int { return $this->id; }
    
    #[TableColumn('inserted')]
    public null|DateTime $insertedTime = null;
    public function getInsertedTime() : DateTime { return $this->insertedTime; }

    #[TableColumn('updated')]
    public null|DateTime $updatedTime = null;
    #[TableColumn('name')]
    public string $name;
    #[TableColumn('obj_int')]
    public int $int;
    #[TableColumn('obj_float')]
    public float $float;
    #[TableColumn('obj_bool')]
    public bool $bool;

    public function __construct(string $name, int $int, float $float, bool $bool)
    {
        $this->name = $name;
        $this->int = $int;
        $this->float = $float;
        $this->bool = $bool;

        parent::__construct();
    }

    public static function Blank() : TestObject {
        return new TestObject("",0,0.0,false);
    }

    public static function LoadFromId(int $id) : ?TestObject {
        // 1. need to run a query like:
        //     SELECT * FROM [tablename] WHERE [idcolumnname] = [id];
        $table = self::getTable();
        $results = $table->Select(null, (new Where())->Equal($table->GetPrimaryKeyColumn()->columnName, $id));
        // 2. check that there is a single result
        if (count($results) != 1) {
            return null;
        }
        // 3. 'cast' result to an instance of TestObject
        // 4. return instance
        return self::CastFromRow($results[0]);
    }

    /**
     * @return TestObject[]
     */
    public static function GetAll() : array {
        // 1. need to run a query like:
        //     SELECT * FROM [tablename];
        //     (until easy-to-use querying is implemented, can just use
        //      self::table->dbi->query($query, $params) to run the query
        //      directly)
        $table = self::getTable();
        $results = $table->Select();
        // 3. 'cast' each result to an instance of TestObject
        // 4. return array of instances

        return array_map(fn($row)=>self::CastFromRow($row), $results);
    }

    public function Save() : bool {
        $now = new DateTime(timezone:new DateTimeZone('UTC'));
        $this->updatedTime = $now;
        if ($this->insertedTime === null) {
            $this->insertedTime = $now;
        }

        return $this->UpdateOrCreateEntry();
    }

    public function Delete() : bool {
        return $this->DeleteEntry();
    }
}