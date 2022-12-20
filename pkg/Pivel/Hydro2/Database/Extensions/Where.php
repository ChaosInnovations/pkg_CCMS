<?php

namespace Package\Pivel\Hydro2\Database\Extensions;

class Where
{
    private string $queryString = '';
    private array $params = [];
    private int $nextParamNumber = 0;

    public function __construct() {

    }

    public function Equal($columnName, $value) : Where {
        $param = 'whereparam'.$this->nextParamNumber++;
        $this->queryString .= $columnName.'=:'.$param;
        $this->params[$param] = $value;
        return $this;
    }

    public function GetParameterizedQueryString() : string {
        return 'WHERE '.$this->queryString;
    }

    public function GetParameters() : array {
        return $this->params;
    }
}