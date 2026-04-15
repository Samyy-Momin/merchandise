<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Services\Invoice\InvoiceServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceServiceInterface $invoiceService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $invoices = $this->invoiceService->listInvoices($request->only(['status', 'customer_id']));

        return response()->json([
            'data' => $invoices->items(),
            'meta' => [
                'total' => $invoices->total(),
                'per_page' => $invoices->perPage(),
                'current_page' => $invoices->currentPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $invoice = $this->invoiceService->getInvoice($id);

        return response()->json(['data' => $this->serializeInvoice($invoice)]);
    }

    public function create(int $id): JsonResponse
    {
        $invoice = $this->invoiceService->createInvoice($id);

        return response()->json(['data' => $this->serializeInvoice($invoice)], 201);
    }

    public function recordPayment(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'amount_cents' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', 'string'],
            'reference' => ['nullable', 'string'],
        ]);

        $payment = $this->invoiceService->recordPayment(
            $id,
            (int) $validated['amount_cents'],
            $validated['payment_method'],
            $validated['reference'] ?? null,
        );

        return response()->json(['data' => $payment], 201);
    }

    public function download(int $id): JsonResponse
    {
        $invoice = $this->invoiceService->getInvoice($id);

        return response()->json(['data' => $this->serializeInvoice($invoice)]);
    }

    private function serializeInvoice(mixed $invoice): array
    {
        return array_merge($invoice->toArray(), [
            'due_date' => $invoice->due_date?->toDateString(),
        ]);
    }
}
