<?php

declare(strict_types=1);

namespace App\Services\Acknowledgement;

use App\Enums\AcknowledgementStatus;
use App\Enums\OrderStatus;
use App\Events\MerchandiseAcknowledgementApproved;
use App\Events\MerchandiseAcknowledgementRejected;
use App\Events\MerchandiseDeliveryAcknowledged;
use App\Exceptions\InvalidAcknowledgementStateException;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\MerchandiseAcknowledgement;
use App\Repositories\Interfaces\AcknowledgementRepositoryInterface;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use App\Services\Invoice\InvoiceServiceInterface;
use Illuminate\Support\Facades\DB;

class AcknowledgementService implements AcknowledgementServiceInterface
{
    public function __construct(
        private readonly AcknowledgementRepositoryInterface $ackRepo,
        private readonly OrderRepositoryInterface $orderRepo,
        private readonly InvoiceServiceInterface $invoiceService,
    ) {}

    public function acknowledge(int $orderId, int $customerId, ?string $notes): MerchandiseAcknowledgement
    {
        $txStarted = false;

        try {
            DB::beginTransaction();
            $txStarted = true;

            $order = $this->orderRepo->findOrFail($orderId);

            if ($order->status !== OrderStatus::Dispatched) {
                throw new InvalidOrderTransitionException(
                    "Cannot acknowledge order in status [{$order->status->value}]."
                );
            }

            $ack = $this->ackRepo->create([
                'company_id' => $order->company_id,
                'order_id' => $order->id,
                'acknowledged_by' => $customerId,
                'acknowledged_at' => now(),
                'notes' => $notes,
                'status' => AcknowledgementStatus::Pending,
            ]);

            $this->orderRepo->updateStatus($order, OrderStatus::Acknowledged);

            DB::commit();
            $txStarted = false;

            event(new MerchandiseDeliveryAcknowledged($order->id, ['acknowledged_by' => $customerId]));

            return $ack;
        } catch (\Throwable $e) {
            if ($txStarted) {
                DB::rollBack();
            }
            throw $e;
        }
    }

    public function approveAcknowledgement(int $ackId, int $staffId): MerchandiseAcknowledgement
    {
        $txStarted = false;

        try {
            DB::beginTransaction();
            $txStarted = true;

            $ack = $this->ackRepo->findOrFail($ackId);

            if ($ack->status !== AcknowledgementStatus::Pending) {
                throw new InvalidAcknowledgementStateException(
                    "Acknowledgement #{$ackId} is already [{$ack->status->value}]."
                );
            }

            $approved = $this->ackRepo->approve($ack, $staffId);

            $order = $ack->order;
            $this->orderRepo->updateStatus($order, OrderStatus::InvoiceGenerated);

            DB::commit();
            $txStarted = false;

            event(new MerchandiseAcknowledgementApproved($ack->order_id, ['reviewed_by' => $staffId]));

            // Trigger invoice creation after commit (BRD gate: only after ack approved)
            $this->invoiceService->createInvoice($ack->order_id);
            $this->orderRepo->updateStatus($order, OrderStatus::InvoiceGenerated);

            return $approved;
        } catch (\Throwable $e) {
            if ($txStarted) {
                DB::rollBack();
            }
            throw $e;
        }
    }

    public function rejectAcknowledgement(int $ackId, int $staffId, string $reason): MerchandiseAcknowledgement
    {
        $txStarted = false;

        try {
            DB::beginTransaction();
            $txStarted = true;

            $ack = $this->ackRepo->findOrFail($ackId);

            if ($ack->status !== AcknowledgementStatus::Pending) {
                throw new InvalidAcknowledgementStateException(
                    "Acknowledgement #{$ackId} is already [{$ack->status->value}]."
                );
            }

            $rejected = $this->ackRepo->reject($ack, $staffId, $reason);

            $order = $ack->order;
            $this->orderRepo->updateStatus($order, OrderStatus::Dispatched);

            DB::commit();
            $txStarted = false;

            event(new MerchandiseAcknowledgementRejected($ack->order_id, ['reviewed_by' => $staffId, 'reason' => $reason]));

            return $rejected;
        } catch (\Throwable $e) {
            if ($txStarted) {
                DB::rollBack();
            }
            throw $e;
        }
    }
}
