<?php

declare(strict_types=1);

namespace App\Services\Dispatch;

use App\DTOs\DispatchDTO;
use App\Models\MerchandiseDispatch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DispatchServiceInterface
{
    public function dispatch(int $orderId, DispatchDTO $dto): MerchandiseDispatch;

    public function listDispatches(array $filters): LengthAwarePaginator;
}
