<?php

namespace App\Exceptions;

use RuntimeException;

class InvoiceNotFoundException extends RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("Invoice [{$id}] not found.");
    }
}
