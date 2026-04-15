<?php

declare(strict_types=1);

namespace App\Services\Invoice;

use App\Models\MerchandiseInvoice;
use App\Models\MerchandisePayment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface InvoiceServiceInterface
{
    public function createInvoice(int $orderId): MerchandiseInvoice;

    public function recordPayment(int $invoiceId, int $amountCents, string $method, ?string $ref): MerchandisePayment;

    public function getInvoice(int $id): MerchandiseInvoice;

    public function listInvoices(array $filters): LengthAwarePaginator;
}
