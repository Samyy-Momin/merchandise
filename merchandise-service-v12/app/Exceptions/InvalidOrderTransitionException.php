<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidOrderTransitionException extends RuntimeException
{
    public function __construct(string $from, string $to = '')
    {
        parent::__construct("Cannot transition order from [{$from}]".($to ? " to [{$to}]" : '').'.');
    }
}
