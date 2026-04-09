<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;
use App\Jobs\GenerateInvoicePdf;
use App\Jobs\GenerateInvoiceExcel;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->attributes->get('kc_user');
        $userId = $user['sub'] ?? null;
        $roles = $request->attributes->get('kc_roles', []);

        $query = Invoice::with(['order.shipment','order.address']);

        if (in_array('approver', $roles, true)) {
            // Approver sees all invoices
        } elseif (in_array('vendor', $roles, true)) {
            // Vendor sees invoices for shipments they own
            $query->whereHas('order.shipment', function ($q) use ($userId) {
                $q->where('vendor_id', $userId);
            });
        } else {
            // Buyer sees own invoices
            $query->whereHas('order', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
        }

        $invoices = $query->orderByDesc('id')->paginate(20);
        return response()->json($invoices);
    }

    public function byOrder(Request $request, int $orderId)
    {
        $user = $request->attributes->get('kc_user');
        $userId = $user['sub'] ?? null;
        $roles = $request->attributes->get('kc_roles', []);
        $inv = Invoice::with('order.shipment')->where('order_id', $orderId);

        if (in_array('approver', $roles, true)) {
            // ok
        } elseif (in_array('vendor', $roles, true)) {
            $inv->whereHas('order.shipment', function ($q) use ($userId) { $q->where('vendor_id', $userId); });
        } else {
            $inv->whereHas('order', function ($q) use ($userId) { $q->where('user_id', $userId); });
        }

        $invoice = $inv->firstOrFail();
        return response()->json($invoice);
    }

    public function pdf(Request $request, int $orderId)
    {
        $invoice = $this->findAuthorizedInvoice($request, $orderId);
        if ($request->boolean('async')) {
            GenerateInvoicePdf::dispatch($orderId);
            return response()->json(['status' => 'queued', 'message' => 'Invoice PDF generation queued'], 202);
        }
        // Aggregate full details for template
        $order = Order::with(['items.product','address','shipment.items','acknowledgements.items'])->findOrFail($orderId);

        $deliveredByItem = collect(optional($order->shipment)->items ?? [])->groupBy('order_item_id')->map(fn($g) => (int)$g->sum('delivered_qty'));
        $ackItems = collect();
        foreach ($order->acknowledgements as $ack) foreach ($ack->items as $it) $ackItems->push($it);
        $receivedByItem = $ackItems->groupBy('order_item_id')->map(function ($g) {
            return (int) $g->sum('received_qty');
        });
        $items = [];
        foreach ($order->items as $it) {
            $price = (float) $it->price;
            $delivered = (int) ($deliveredByItem->get($it->id) ?? 0);
            $received = (int) ($receivedByItem->get($it->id) ?? 0);
            $items[] = [
                'product_name' => $it->product->name ?? '',
                'requested_qty' => (int) $it->qty_requested,
                'approved_qty' => (int) ($it->qty_approved ?? 0),
                'delivered_qty' => $delivered,
                'received_qty' => $received,
                'price' => $price,
                'line_total' => $price * $delivered,
            ];
        }

        $data = [
            'invoice' => $invoice,
            'order' => $order,
            'items' => $items,
            'address' => $order->address,
            'buyer_name' => optional($order->acknowledgements->sortByDesc('id')->first())->receiver_name,
        ];

        if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return response()->json(['message' => 'PDF library not installed. Run: composer require barryvdh/laravel-dompdf'], 501);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('invoices.pdf', $data);
        return $pdf->download($invoice->invoice_number . '.pdf');
    }

    public function excel(Request $request, int $orderId)
    {
        $invoice = $this->findAuthorizedInvoice($request, $orderId);
        if ($request->boolean('async')) {
            GenerateInvoiceExcel::dispatch($orderId);
            return response()->json(['status' => 'queued', 'message' => 'Invoice Excel generation queued'], 202);
        }
        $order = Order::with(['items.product','shipment.items','acknowledgements.items'])->findOrFail($orderId);
        $deliveredByItem = collect(optional($order->shipment)->items ?? [])->groupBy('order_item_id')->map(fn($g) => (int)$g->sum('delivered_qty'));
        $ackItems = collect();
        foreach ($order->acknowledgements as $ack) foreach ($ack->items as $it) $ackItems->push($it);
        $receivedByItem = $ackItems->groupBy('order_item_id')->map(fn($g) => (int)$g->sum('received_qty'));
        $rows = [];
        $rows[] = ['Product Name','Requested Qty','Approved Qty','Delivered Qty','Received Qty','Price','Line Total'];
        foreach ($order->items as $it) {
            $price = (float) $it->price;
            $delivered = (int) ($deliveredByItem->get($it->id) ?? 0);
            $received = (int) ($receivedByItem->get($it->id) ?? 0);
            $rows[] = [
                $it->product->name ?? '',
                (int) $it->qty_requested,
                (int) ($it->qty_approved ?? 0),
                $delivered,
                $received,
                $price,
                $price * $delivered,
            ];
        }

        if (!class_exists(\Maatwebsite\Excel\Facades\Excel::class)) {
            Log::error('Excel export failed: maatwebsite/excel facade not available');
            return response()->json(['message' => 'Excel library not installed. Run: composer require maatwebsite/excel'], 501);
        }
        if (!interface_exists(\Maatwebsite\Excel\Concerns\FromArray::class)) {
            Log::error('Excel export failed: incompatible maatwebsite/excel version (Concerns\\FromArray missing)');
            return response()->json([
                'message' => 'Incompatible Excel package version. Install Laravel Excel 3.x',
                'install_hint' => 'composer require maatwebsite/excel:^3.1'
            ], 501);
        }

        $export = new class($rows) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithStyles {
            private $rows; public function __construct($rows){ $this->rows=$rows; }
            public function array(): array { return $this->rows; }
            public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet) { $sheet->getStyle('A1:G1')->getFont()->setBold(true); return []; }
        };

        try {
            return \Maatwebsite\Excel\Facades\Excel::download($export, $invoice->invoice_number . '.xlsx');
        } catch (\Throwable $e) {
            Log::error('Excel export threw exception', [
                'order_id' => $orderId,
                'invoice_id' => $invoice->id ?? null,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Failed to generate Excel', 'error' => $e->getMessage()], 500);
        }
    }

    private function findAuthorizedInvoice(Request $request, int $orderId): Invoice
    {
        $user = $request->attributes->get('kc_user');
        $userId = $user['sub'] ?? null;
        $roles = $request->attributes->get('kc_roles', []);
        $inv = Invoice::with('order.shipment')->where('order_id', $orderId);
        if (in_array('approver', $roles, true)) {
            // ok
        } elseif (in_array('vendor', $roles, true)) {
            $inv->whereHas('order.shipment', function ($q) use ($userId) { $q->where('vendor_id', $userId); });
        } else {
            $inv->whereHas('order', function ($q) use ($userId) { $q->where('user_id', $userId); });
        }
        return $inv->firstOrFail();
    }
}
