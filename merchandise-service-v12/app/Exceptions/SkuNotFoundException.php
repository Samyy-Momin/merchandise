<?php

namespace App\Exceptions;

use RuntimeException;

class SkuNotFoundException extends RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("Merchandise SKU [{$id}] not found.");
    }
}
