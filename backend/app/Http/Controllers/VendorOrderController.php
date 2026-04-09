<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Shipment;
use App\Models\TrackingLog;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VendorOrderController extends Controller
{
    public function list(Request $request)
    {
        $filter = $request->query('filter');
        $query = Order::with(['items.product', 'address']);

        // Vendor should see full lifecycle, with optional filters
        if ($filter) {
            $map = [
                'ready' => ['approved','partially_approved'],
                'processing' => ['processing'],
                'dispatched' => ['dispatched'],
                'in_transit' => ['in_transit'],
                'delivered' => ['delivered'],
                'completed' => ['completed'],
            ];
            if (isset($map[$filter])) {
                $query->whereIn('status', $map[$filter]);
            }
        }

        $orders = $query->orderByDesc('id')->paginate(20);
        return response()->json($orders);
    }

    public function process(Request $request, int $id)
    {
        $user = $request->attributes->get('kc_user');
        $vendorId = $user['sub'] ?? 'unknown';

        return DB::transaction(function () use ($id, $vendorId) {
            $order = Order::with('shipment')->findOrFail($id);
            if (!in_array($order->status, ['approved','partially_approved'], true)) {
                return response()->json(['message' => 'Order cannot be moved to processing from its current status'], 422);
            }

            $order->status = 'processing';
            $order->save();

            $shipment = $order->shipment;
            if (!$shipment) {
                $shipment = Shipment::create([
                    'order_id' => $order->id,
                    'vendor_id' => $vendorId,
                    'status' => 'processing',
                ]);
            } else {
                $shipment->status = 'processing';
                $shipment->save();
            }
            TrackingLog::create(['shipment_id' => $shipment->id, 'status' => 'processing', 'message' => 'Order moved to processing']);

            Log::info('Vendor processing started', ['order_id' => $order->id, 'vendor_id' => $vendorId]);

            return response()->json($order->load(['shipment']));
        });
    }

    public function dispatch(Request $request, int $id)
    {
        $data = $request->validate([
            'tracking_number' => 'required|string|max:255',
            'courier_name' => 'required|string|max:255',
            'estimated_delivery_date' => 'nullable|date',
        ]);

        return DB::transaction(function () use ($id, $data) {
            $order = Order::with('shipment')->findOrFail($id);
            if ($order->status !== 'processing') {
                return response()->json(['message' => 'Order must be in processing to dispatch'], 422);
            }
            $shipment = $order->shipment;
            if (!$shipment) {
                return response()->json(['message' => 'Shipment not found for order'], 422);
            }

            $order->status = 'dispatched';
            $order->save();

            $shipment->status = 'dispatched';
            $shipment->tracking_number = $data['tracking_number'];
            $shipment->courier_name = $data['courier_name'];
            $shipment->estimated_delivery_date = $data['estimated_delivery_date'] ?? null;
            $shipment->save();

            TrackingLog::create(['shipment_id' => $shipment->id, 'status' => 'dispatched', 'message' => 'Shipment dispatched']);

            return response()->json($order->load(['shipment']));
        });
    }

    public function transit(Request $request, int $id)
    {
        return DB::transaction(function () use ($id) {
            $order = Order::with('shipment')->findOrFail($id);
            if ($order->status !== 'dispatched') {
                return response()->json(['message' => 'Order must be dispatched to mark in transit'], 422);
            }
            $shipment = $order->shipment;
            if (!$shipment) {
                return response()->json(['message' => 'Shipment not found for order'], 422);
            }

            $order->status = 'in_transit';
            $order->save();

            $shipment->status = 'in_transit';
            $shipment->save();
            TrackingLog::create(['shipment_id' => $shipment->id, 'status' => 'in_transit', 'message' => 'Shipment in transit']);

            return response()->json($order->load(['shipment']));
        });
    }

    public function deliver(Request $request, int $id)
    {
        $data = $request->validate([
            'items' => 'nullable|array',
            'items.*.order_item_id' => 'required|integer|exists:order_items,id',
            'items.*.delivered_qty' => 'required|integer|min:0',
        ]);

        return DB::transaction(function () use ($id, $data) {
            $order = Order::with(['shipment','items'])->findOrFail($id);
            if ($order->status !== 'in_transit') {
                return response()->json(['message' => 'Order must be in transit to mark delivered'], 422);
            }
            $shipment = $order->shipment;
            if (!$shipment) {
                return response()->json(['message' => 'Shipment not found for order'], 422);
            }

            // Optional per-item delivered quantities
            if (!empty($data['items'])) {
                $itemsMap = collect($order->items)->keyBy('id');
                foreach ($data['items'] as $row) {
                    $oi = $itemsMap->get($row['order_item_id']);
                    if (!$oi) continue;
                    $delivered = (int) $row['delivered_qty'];
                    // safety: cannot exceed approved
                    $cap = (int) ($oi->qty_approved ?? 0);
                    if ($delivered > $cap) { $delivered = $cap; }
                    \App\Models\ShipmentItem::updateOrCreate(
                        ['shipment_id' => $shipment->id, 'order_item_id' => $oi->id],
                        ['delivered_qty' => $delivered]
                    );
                }
            }

            $order->status = 'delivered';
            $order->save();

            $shipment->status = 'delivered';
            $shipment->save();
            TrackingLog::create(['shipment_id' => $shipment->id, 'status' => 'delivered', 'message' => 'Shipment delivered']);

            // Ensure invoice exists after delivered
            if (!$order->invoice) {
                $total = 0;
                // compute from shipment_items if exist, else from approved
                $deliveredByItem = collect(optional($shipment)->items ?? [])->keyBy('order_item_id');
                foreach ($order->items as $it) {
                    $delivered = (int) optional($deliveredByItem->get($it->id))->delivered_qty ?? (int) ($it->qty_approved ?? 0);
                    $total += $delivered * (float) $it->price;
                }
                $invoiceNo = sprintf('INV-%d-%s', $order->id, now()->format('YmdHis'));
                Invoice::create([
                    'order_id' => $order->id,
                    'invoice_number' => $invoiceNo,
                    'total_amount' => $total,
                    'status' => 'open',
                ]);
            }

            Log::info('Vendor marked delivered', ['order_id' => $order->id]);
            return response()->json($order->load(['shipment.items','invoice']));
        });
    }
}
