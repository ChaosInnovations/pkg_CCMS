<?php

namespace Package\Pivel\Hydro2\Database\Extensions;

use Package\Pivel\Hydro2\Database\Models\Order;

class OrderBy
{
    private string $queryString = '';

    public function __construct() {

    }

    public function Column($columnName, Order $order) : OrderBy {
        $this->queryString .= $columnName.' '.$order->value.',';
        return $this;
    }

    public function GetQueryString() : string {
        return 'ORDER BY '.trim($this->queryString,',');
    }
}