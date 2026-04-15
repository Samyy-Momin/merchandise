<?php

declare(strict_types=1);

namespace App\Services\Invoice;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Events\MerchandiseInvoiceGenerated;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\MerchandiseInvoice;
use App\Models\MerchandisePayment;
use App\Repositories\Interfaces\InvoiceRepositoryInterface;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class InvoiceService implements InvoiceServiceInterface
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepo,
        private readonly OrderRepositoryInterface $orderRepo,
    ) {}

    public function createInvoice(int $orderId): MerchandiseInvoice
    {
        $txStarted = false;

        try {
            DB::beginTransaction();
            $txStarted = true;

            $order = $this->orderRepo->findOrFail($orderId);

            if (! $order->status->canTransitionTo(OrderStatus::PaymentPending)) {
                throw new InvalidOrderTransitionException(
                    "Cannot generate invoice for order in status [{$order->status->value}]."
                );
            }

            $dueDate = Carbon::now()->addDays(15);

            $invoice = $this->invoiceRepo->create($order, $dueDate);

            $this->orderRepo->updateStatus($order, OrderStatus::PaymentPending);

            DB::commit();
            $txStarted = false;

            event(new MerchandiseInvoiceGenerated($order->id, ['invoice_number' => $invoice->invoice_number]));

            return $invoice;
        } catch (\Throwable $e) {
            if ($txStarted) {
                DB::rollBack();
            }
            throw $e;
        }
    }

    public function recordPayment(int $invoiceId, int $amountCents, string $method, ?string $ref): MerchandisePayment
    {
        $txStarted = false;

        try {
            DB::beginTransaction();
            $txStarted = true;

            $invoice = $this->invoiceRepo->findOrFail($invoiceId);

            $payment = $this->invoiceRepo->recordPayment($invoice, $amountCents, $method, $ref);

            $newAmountPaid = $invoice->amount_paid_cents + $amountCents;

            if ($newAmountPaid >= $invoice->total_cents) {
                $this->invoiceRepo->updateStatus($invoice, InvoiceStatus::Paid);
                $this->orderRepo->updateStatus($invoice->order, OrderStatus::Completed);
            } elseif (Carbon::parse($invoice->due_date)->isPast()) {
                $this->invoiceRepo->updateStatus($invoice, InvoiceStatus::Overdue);
                $this->orderRepo->updateStatus($invoice->order, OrderStatus::Overdue);
            }

            DB::commit();
            $txStarted = false;

            return $payment;
        } catch (\Throwable $e) {
            if ($txStarted) {
                DB::rollBack();
            }
            throw $e;
        }
    }

    public function getInvoice(int $id): MerchandiseInvoice
    {
        return $this->invoiceRepo->findOrFail($id);
    }

    public function listInvoices(array $filters): LengthAwarePaginator
    {
        return $this->invoiceRepo->list($filters);
    }
}
