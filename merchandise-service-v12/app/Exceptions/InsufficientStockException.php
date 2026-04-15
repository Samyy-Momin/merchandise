<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(string $skuCode, int $requested, int $available)
    {
        parent::__construct("SKU [{$skuCode}]: requested {$requested}, only {$available} available.");
    }
}
