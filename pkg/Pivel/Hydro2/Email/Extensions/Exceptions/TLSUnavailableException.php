<?php

namespace Package\Pivel\Hydro2\Email\Extensions\Exceptions;

use Exception;
use Throwable;

class TLSUnavailableException extends Exception
{
    public function __construct(string $message, string|int $code=0, null|Throwable $previous=null) {
        // some code

        // make sure everything is assigned properly
        parent::__construct($message, $code, $previous);
    }
}