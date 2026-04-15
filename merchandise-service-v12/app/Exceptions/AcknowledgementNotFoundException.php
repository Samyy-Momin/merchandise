<?php

namespace App\Exceptions;

use RuntimeException;

class AcknowledgementNotFoundException extends RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("Acknowledgement [{$id}] not found.");
    }
}
