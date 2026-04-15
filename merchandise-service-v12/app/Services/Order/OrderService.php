<?php

declare(strict_types=1);

namespace App\Services\Order;

use App\DTOs\OrderDTO;
use App\Enums\OrderStatus;
use App\Events\MerchandiseOrderPlaced;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidOrderTransitionException;
use App\Exceptions\UnauthorizedOrderCancellationException;
use App\Models\MerchandiseOrder;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use App\Repositories\Interfaces\SkuRepositoryInterface;
use App\Services\Approval\ApprovalWorkflowServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OrderService implements OrderServiceInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepo,
        private readonly SkuRepositoryInterface $skuRepo,
        private readonly ApprovalWorkflowServiceInterface $approvalWorkflowService,
    ) {}

    public function placeOrder(OrderDTO $dto): MerchandiseOrder
    {
        $txStarted = false;

        try {
            DB::beginTransaction();
            $txStarted = true;

            $orderRef = 'MORD-'.now()->format('Ymd').'-'.str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);

            $items = [];
            $totalCents = 0;

            foreach ($dto->items as $item) {
                $sku = $this->skuRepo->findOrFail($item->skuId);

                if (! $sku->is_active) {
                    throw new InvalidOrderTransitionException(
                        "Cannot place order with inactive SKU [{$sku->sku_code}]."
                    );
                }

                if ($sku->stock_quantity < $item->requestedQuantity) {
                    throw new InsufficientStockException(
                        (string) $sku->sku_code,
                        (int) $item->requestedQuantity,
                        (int) $sku->stock_quantity,
                    );
                }

                $lineTotalCents = $item->requestedQuantity * $sku->unit_price_cents;
                $totalCents += $lineTotalCents;

                $items[] = [
                    'sku_id' => $sku->id,
                    'sku_code' => $sku->sku_code,
                    'sku_name' => $sku->name,
                    'requested_quantity' => $item->requestedQuantity,
                    'unit_price_cents' => $sku->unit_price_cents,
                    'line_total_cents' => $lineTotalCents,
                ];
            }

            $orderData = [
                'company_id' => $dto->companyId,
                'order_ref' => $orderRef,
                'customer_id' => $dto->customerId,
                'order_kind' => $dto->orderKind,
                'buyer_store_id' => $dto->buyerStoreId,
                'vendor_store_id' => $dto->vendorStoreId,
                'fulfillment_store_id' => $dto->fulfillmentStoreId,
                'status' => OrderStatus::PendingApproval,
                'notes' => $dto->notes,
                'subtotal_cents' => $totalCents,
                'total_cents' => $totalCents,
            ];

            $order = $this->orderRepo->create($orderData, $items);

            if ($dto->orderKind === 'procurement') {
                $approvalRequest = $this->approvalWorkflowService->createPending([
                    'company_id' => $dto->companyId,
                    'entity_type' => 'merchandise_order',
                    'entity_id' => (int) $order->id,
                    'buyer_store_id' => $dto->buyerStoreId,
                    'vendor_store_id' => $dto->vendorStoreId,
                    'requested_by' => $dto->customerId,
                    'approver_role' => 'admin',
                ]);

                $order->update([
                    'approval_request_id' => $approvalRequest->id,
                ]);
            }

            $order = $order->fresh(['items']);

            DB::commit();
            $txStarted = false;

            event(new MerchandiseOrderPlaced($order->id, ['order_ref' => $order->order_ref]));

            return $order;
        } catch (\Throwable $e) {
            if ($txStarted) {
                DB::rollBack();
            }
            throw $e;
        }
    }

    public function cancelOrder(int $orderId, int $customerId): void
    {
        $txStarted = false;

        try {
            DB::beginTransaction();
            $txStarted = true;

            $order = $this->orderRepo->findOrFail($orderId);

            if ($order->customer_id !== $customerId) {
                throw new UnauthorizedOrderCancellationException('You are not authorized to cancel this order.');
            }

            if (! $order->isCancellable()) {
                throw new InvalidOrderTransitionException(
                    "Cannot cancel order in status [{$order->status->value}]."
                );
            }

            $this->orderRepo->cancel($order);

            DB::commit();
            $txStarted = false;
        } catch (\Throwable $e) {
            if ($txStarted) {
                DB::rollBack();
            }
            throw $e;
        }
    }

    public function listOrders(array $filters): LengthAwarePaginator
    {
        return $this->orderRepo->list($filters);
    }

    public function findOrFail(int $id): MerchandiseOrder
    {
        return $this->orderRepo->findOrFail($id);
    }
}
