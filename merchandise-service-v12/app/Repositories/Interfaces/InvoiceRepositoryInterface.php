<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Enums\InvoiceStatus;
use App\Models\MerchandiseInvoice;
use App\Models\MerchandiseOrder;
use App\Models\MerchandisePayment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

interface InvoiceRepositoryInterface
{
    public function create(MerchandiseOrder $order, Carbon $dueDate): MerchandiseInvoice;

    public function findOrFail(int $id): MerchandiseInvoice;

    public function recordPayment(MerchandiseInvoice $invoice, int $amountCents, string $method, ?string $ref): MerchandisePayment;

    public function updateStatus(MerchandiseInvoice $invoice, InvoiceStatus $status): void;

    public function list(array $filters): LengthAwarePaginator;
}
