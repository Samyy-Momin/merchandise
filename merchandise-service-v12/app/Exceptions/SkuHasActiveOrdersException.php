<?php

namespace App\Exceptions;

use RuntimeException;

class SkuHasActiveOrdersException extends RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("SKU [{$id}] has active orders and cannot be deleted.");
    }
}
