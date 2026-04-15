<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\OrderStatus;
use App\Exceptions\OrderNotFoundException;
use App\Models\MerchandiseOrder;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderRepository implements OrderRepositoryInterface
{
    public function list(array $filters): LengthAwarePaginator
    {
        $query = MerchandiseOrder::with('items');

        if (isset($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('id', 'desc')->paginate(20);
    }

    public function findOrFail(int $id): MerchandiseOrder
    {
        $order = MerchandiseOrder::with('items')->find($id);

        if ($order === null) {
            throw new OrderNotFoundException($id);
        }

        return $order;
    }

    public function create(array $orderData, array $items): MerchandiseOrder
    {
        $order = MerchandiseOrder::create($orderData);

        foreach ($items as $item) {
            $order->items()->create(array_merge($item, ['company_id' => $order->company_id]));
        }

        $order->load('items');

        return $order;
    }

    public function updateStatus(MerchandiseOrder $order, OrderStatus $status): void
    {
        $order->update(['status' => $status]);
    }

    public function cancel(MerchandiseOrder $order): void
    {
        $order->update(['status' => OrderStatus::Cancelled]);
    }

    public function applyApproval(MerchandiseOrder $order, array $resolvedItems, OrderStatus $status, int $staffId): MerchandiseOrder
    {
        $subtotalCents = 0;

        foreach ($resolvedItems as $resolved) {
            $order->items()->where('id', $resolved['item_id'])->update([
                'approved_quantity' => $resolved['approved_quantity'],
                'line_total_cents' => $resolved['line_total_cents'],
            ]);
            $subtotalCents += $resolved['line_total_cents'];
        }

        $order->update([
            'status' => $status,
            'subtotal_cents' => $subtotalCents,
            'total_cents' => $subtotalCents,
            'approved_by' => $staffId,
            'approved_at' => now(),
        ]);

        return $order->fresh(['items']);
    }

    public function reject(MerchandiseOrder $order, int $staffId, string $reason): MerchandiseOrder
    {
        $order->update([
            'status' => OrderStatus::Rejected,
            'rejected_reason' => $reason,
        ]);

        return $order->fresh();
    }
}
