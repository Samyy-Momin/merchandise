<?php

declare(strict_types=1);

namespace App\Services\Order;

use App\DTOs\OrderDTO;
use App\Models\MerchandiseOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OrderServiceInterface
{
    public function placeOrder(OrderDTO $dto): MerchandiseOrder;

    public function cancelOrder(int $orderId, int $customerId): void;

    public function listOrders(array $filters): LengthAwarePaginator;

    public function findOrFail(int $id): MerchandiseOrder;
}
