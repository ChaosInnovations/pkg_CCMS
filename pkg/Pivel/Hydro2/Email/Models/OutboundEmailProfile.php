<?php

namespace Package\Pivel\Hydro2\Email\Models;

use Package\Pivel\Hydro2\Database\Extensions\TableColumn;
use Package\Pivel\Hydro2\Database\Extensions\TableName;
use Package\Pivel\Hydro2\Database\Models\BaseObject;

#[TableName('outbound_email_profiles')]
class OutboundEmailProfile extends BaseObject
{
    #[TableColumn('id', autoIncrement:true)]
    public ?int $Id;
    #[TableColumn('name')]
    public string $Name;

    public function __construct(?int $id=null, string $name='') {
        $this->Id = $id;
        $this->Name = $name;

        parent::__construct();
    }
}