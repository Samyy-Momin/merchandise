<?php

namespace App\Exceptions;

use RuntimeException;

class OrderNotFoundException extends RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("Merchandise order [{$id}] not found.");
    }
}
