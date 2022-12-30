<?php

namespace Package\Pivel\Hydro2\Email\Models;

use Package\Pivel\Hydro2\Database\Extensions\TableColumn;
use Package\Pivel\Hydro2\Database\Extensions\TableName;
use Package\Pivel\Hydro2\Database\Extensions\TablePrimaryKey;
use Package\Pivel\Hydro2\Database\Extensions\Where;
use Package\Pivel\Hydro2\Database\Models\BaseObject;

#[TableName('outbound_email_profiles')]
class OutboundEmailProfile extends BaseObject
{
    #[TableColumn('id', autoIncrement:true)]
    #[TablePrimaryKey]
    public ?int $Id;
    #[TableColumn('key')]
    public string $Key;
    #[TableColumn('name')]
    public string $Name;
    #[TableColumn('type')]
    public string $Type;

    public function __construct(?int $id=null, string $name='', string $type='smtp') {
        $this->Id = $id;
        $this->Name = $name;
        $this->Type = $type;

        parent::__construct();
    }

    public static function LoadFromKey(string $key) : ?self {
        $table = self::getTable();
        $results = $table->Select(null, (new Where())->Equal('key', $key));
        
        if (count($results) != 1) {
            return null;
        }

        return self::CastFromRow($results[0], className:get_called_class());
    }
}