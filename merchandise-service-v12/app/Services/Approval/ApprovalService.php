<?php

declare(strict_types=1);

namespace App\Services\Approval;

use App\DTOs\ApprovalDTO;
use App\Enums\OrderStatus;
use App\Events\MerchandiseOrderApproved;
use App\Events\MerchandiseOrderPartiallyApproved;
use App\Events\MerchandiseOrderRejected;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\MerchandiseOrder;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ApprovalService implements ApprovalServiceInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepo,
    ) {}

    public function approve(int $orderId, ApprovalDTO $dto): MerchandiseOrder
    {
        $txStarted = false;

        try {
            DB::beginTransaction();
            $txStarted = true;

            $order = $this->orderRepo->findOrFail($orderId);

            if (! $order->isApprovable()) {
                throw new InvalidOrderTransitionException(
                    "Cannot approve order in status [{$order->status->value}]."
                );
            }

            $isPartial = false;
            $itemMap = collect($dto->items)->keyBy('itemId');

            $resolvedItems = $order->items->map(function ($item) use ($itemMap, &$isPartial) {
                $approvalItem = $itemMap->get($item->id);
                $approvedQty = $approvalItem ? $approvalItem->approvedQuantity : $item->requested_quantity;

                if ($approvedQty < $item->requested_quantity) {
                    $isPartial = true;
                }

                return [
                    'item_id' => $item->id,
                    'approved_quantity' => $approvedQty,
                    'line_total_cents' => $approvedQty * $item->unit_price_cents,
                ];
            })->values()->all();

            $newStatus = $isPartial ? OrderStatus::PartiallyApproved : OrderStatus::Approved;

            $result = $this->orderRepo->applyApproval($order, $resolvedItems, $newStatus, $dto->staffId);

            DB::commit();
            $txStarted = false;

            if ($isPartial) {
                event(new MerchandiseOrderPartiallyApproved($order->id, ['approved_by' => $dto->staffId]));
            } else {
                event(new MerchandiseOrderApproved($order->id, ['approved_by' => $dto->staffId]));
            }

            return $result;
        } catch (\Throwable $e) {
            if ($txStarted) {
                DB::rollBack();
            }
            throw $e;
        }
    }

    public function reject(int $orderId, int $staffId, string $reason): MerchandiseOrder
    {
        $txStarted = false;

        try {
            DB::beginTransaction();
            $txStarted = true;

            $order = $this->orderRepo->findOrFail($orderId);

            if (! $order->isApprovable()) {
                throw new InvalidOrderTransitionException(
                    "Cannot reject order in status [{$order->status->value}]."
                );
            }

            $result = $this->orderRepo->reject($order, $staffId, $reason);

            DB::commit();
            $txStarted = false;

            event(new MerchandiseOrderRejected($order->id, ['staff_id' => $staffId, 'reason' => $reason]));

            return $result;
        } catch (\Throwable $e) {
            if ($txStarted) {
                DB::rollBack();
            }
            throw $e;
        }
    }
}
