<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidAcknowledgementStateException extends RuntimeException
{
    public function __construct(string $status)
    {
        parent::__construct("Acknowledgement in status [{$status}] cannot be transitioned.");
    }
}
