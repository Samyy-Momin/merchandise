<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MerchandiseOrderDispatched
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly int $orderId, public readonly array $payload = []) {}
}
