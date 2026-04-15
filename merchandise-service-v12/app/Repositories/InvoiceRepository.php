<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\InvoiceStatus;
use App\Exceptions\InvoiceNotFoundException;
use App\Models\MerchandiseInvoice;
use App\Models\MerchandiseOrder;
use App\Models\MerchandisePayment;
use App\Repositories\Interfaces\InvoiceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class InvoiceRepository implements InvoiceRepositoryInterface
{
    public function create(MerchandiseOrder $order, Carbon $dueDate): MerchandiseInvoice
    {
        $invoiceNumber = 'MINV-'.now()->format('Ym').'-'.str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);

        return MerchandiseInvoice::create([
            'company_id' => $order->company_id,
            'invoice_number' => $invoiceNumber,
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'status' => InvoiceStatus::PaymentPending,
            'subtotal_cents' => $order->subtotal_cents ?? $order->total_cents,
            'tax_cents' => 0,
            'discount_cents' => 0,
            'total_cents' => $order->total_cents,
            'amount_paid_cents' => 0,
            'due_date' => $dueDate->toDateString(),
        ]);
    }

    public function findOrFail(int $id): MerchandiseInvoice
    {
        $invoice = MerchandiseInvoice::with(['order', 'payments'])->find($id);

        if ($invoice === null) {
            throw new InvoiceNotFoundException("Invoice #{$id} not found.");
        }

        return $invoice;
    }

    public function recordPayment(MerchandiseInvoice $invoice, int $amountCents, string $method, ?string $ref): MerchandisePayment
    {
        $payment = $invoice->payments()->create([
            'company_id' => $invoice->company_id,
            'amount_cents' => $amountCents,
            'payment_method' => $method,
            'reference' => $ref,
            'paid_at' => now(),
        ]);

        $invoice->increment('amount_paid_cents', $amountCents);

        return $payment;
    }

    public function updateStatus(MerchandiseInvoice $invoice, InvoiceStatus $status): void
    {
        $invoice->update(['status' => $status]);
    }

    public function list(array $filters): LengthAwarePaginator
    {
        $query = MerchandiseInvoice::with('order');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        return $query->orderBy('id', 'desc')->paginate(20);
    }
}
