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

    public function __construct(
        string $name = '',
        int $int = 0,
        float $float = 0.0,
        bool $bool = false,
        ) {
        $this->name = $name;
        $this->int = $int;
        $this->float = $float;
        $this->bool = $bool;

        parent::__construct();
    }

    public static function Blank() : TestObject {
        return new TestObject("",0,0.0,false);
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