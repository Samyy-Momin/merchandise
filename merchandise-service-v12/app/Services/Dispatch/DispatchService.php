<?php

declare(strict_types=1);

namespace App\Services\Dispatch;

use App\DTOs\DispatchDTO;
use App\Enums\OrderStatus;
use App\Events\MerchandiseOrderDispatched;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\MerchandiseDispatch;
use App\Repositories\Interfaces\DispatchRepositoryInterface;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DispatchService implements DispatchServiceInterface
{
    private const DISPATCHABLE_STATUSES = [
        OrderStatus::Approved,
        OrderStatus::PartiallyApproved,
        OrderStatus::Processing,
    ];

    public function __construct(
        private readonly DispatchRepositoryInterface $dispatchRepo,
        private readonly OrderRepositoryInterface $orderRepo,
    ) {}

    public function dispatch(int $orderId, DispatchDTO $dto): MerchandiseDispatch
    {
        $txStarted = false;

        try {
            DB::beginTransaction();
            $txStarted = true;

            $order = $this->orderRepo->findOrFail($orderId);

            if (! in_array($order->status, self::DISPATCHABLE_STATUSES, true)) {
                throw new InvalidOrderTransitionException(
                    "Cannot dispatch order in status [{$order->status->value}]."
                );
            }

            $dispatch = $this->dispatchRepo->create([
                'company_id' => $order->company_id,
                'order_id' => $order->id,
                'dispatched_by' => $dto->staffId,
                'courier' => $dto->courier,
                'tracking_number' => $dto->trackingNumber,
                'dispatched_at' => now(),
                'estimated_delivery_at' => $dto->estimatedDeliveryAt,
            ]);

            $this->orderRepo->updateStatus($order, OrderStatus::Dispatched);

            DB::commit();
            $txStarted = false;

            event(new MerchandiseOrderDispatched($order->id, ['dispatched_by' => $dto->staffId]));

            return $dispatch;
        } catch (\Throwable $e) {
            if ($txStarted) {
                DB::rollBack();
            }
            throw $e;
        }
    }

    public function listDispatches(array $filters): LengthAwarePaginator
    {
        return $this->dispatchRepo->list($filters);
    }
}
