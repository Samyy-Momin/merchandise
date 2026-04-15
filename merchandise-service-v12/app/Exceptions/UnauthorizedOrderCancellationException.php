<?php

namespace App\Exceptions;

use RuntimeException;

class UnauthorizedOrderCancellationException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('You are not authorized to cancel this order.');
    }
}
