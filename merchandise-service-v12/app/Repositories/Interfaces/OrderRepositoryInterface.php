<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Enums\OrderStatus;
use App\Models\MerchandiseOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OrderRepositoryInterface
{
    public function list(array $filters): LengthAwarePaginator;

    public function findOrFail(int $id): MerchandiseOrder;

    /** Creates the order and all its line items atomically */
    public function create(array $orderData, array $items): MerchandiseOrder;

    public function updateStatus(MerchandiseOrder $order, OrderStatus $status): void;

    public function cancel(MerchandiseOrder $order): void;

    public function applyApproval(MerchandiseOrder $order, array $resolvedItems, OrderStatus $status, int $staffId): MerchandiseOrder;

    public function reject(MerchandiseOrder $order, int $staffId, string $reason): MerchandiseOrder;
}
