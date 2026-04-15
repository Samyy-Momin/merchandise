<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->attributes->get('kc_user');
        $userId = $user['sub'] ?? null;
        $roles = $request->attributes->get('kc_roles', []);

        $query = Order::with(['items.product', 'address']);

        $filter = $request->query('filter');
        if (in_array('approver', $roles, true)) {
            // Approver sees full lifecycle with optional filters
            if ($filter) {
                $map = [
                    'pending' => ['pending_approval','partially_approved'],
                    'approved' => ['approved'],
                    'in_progress' => ['processing','dispatched','in_transit'],
                    'delivered' => ['delivered'],
                    'completed' => ['completed'],
                    'issues' => ['issue_reported'],
                ];
                if (isset($map[$filter])) $query->whereIn('status', $map[$filter]);
            }
        } else {
            // Buyer sees own orders, with optional filters
            $query->where('user_id', $userId);
            if ($filter) {
                $map = [
                    'pending' => ['pending_approval','partially_approved'],
                    'approved' => ['approved'],
                    'in_progress' => ['processing','dispatched','in_transit'],
                    'delivered' => ['delivered'],
                    'completed' => ['completed'],
                    'issues' => ['issue_reported'],
                ];
                if (isset($map[$filter])) $query->whereIn('status', $map[$filter]);
            }
        }

        $orders = $query->orderByDesc('id')->paginate(20);
        return response()->json($orders);
    }

    public function show(Request $request, int $id)
    {
        $user = $request->attributes->get('kc_user');
        $userId = $user['sub'] ?? null;
        $roles = $request->attributes->get('kc_roles', []);

        $query = Order::with(['items.product', 'address', 'approvals', 'shipment.logs']);
        if (!in_array('approver', $roles, true) && !in_array('vendor', $roles, true) && !in_array('admin', $roles, true) && !in_array('super_admin', $roles, true)) {
            // Buyers can only view their own orders. Approvers and Vendors can view any order.
            $query->where('user_id', $userId);
        }

        $order = $query->findOrFail($id);
        return response()->json($order);
    }

    public function tracking(Request $request, int $id)
    {
        $user = $request->attributes->get('kc_user');
        $userId = $user['sub'] ?? null;
        $roles = $request->attributes->get('kc_roles', []);

        $query = Order::with(['shipment.logs']);
        if (!in_array('approver', $roles, true) && !in_array('vendor', $roles, true) && !in_array('admin', $roles, true) && !in_array('super_admin', $roles, true)) {
            // Buyer must own the order
            $query->where('user_id', $userId);
        }
        $order = $query->findOrFail($id);
        return response()->json($order->shipment);
    }

    public function acknowledge(Request $request, int $id)
    {
        $user = $request->attributes->get('kc_user');
        $userId = $user['sub'] ?? null;

        $order = Order::with(['issues','items'])->findOrFail($id);
        if ($order->user_id !== $userId) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        if ($order->status !== 'delivered') {
            return response()->json(['message' => 'Order must be delivered to acknowledge'], 422);
        }

        $data = $request->validate([
            'employee_code' => 'required|string|max:255',
            'receiver_name' => 'required|string|max:255',
            'branch_manager_name' => 'required|string|max:255',
            'remarks' => 'nullable|string',
            'rating' => 'nullable|integer|min:1|max:5',
            'items' => 'required|array|min:1',
            'items.*.order_item_id' => 'required|integer|exists:order_items,id',
            'items.*.received_qty' => 'required|integer|min:0',
            'items.*.status' => 'required|in:received,not_received',
            'items.*.comment' => 'nullable|string',
        ]);

        // Build map of order items for validation (delivered_qty approximated as qty_approved)
        $orderItems = $order->items->keyBy('id');
        $violations = [];

        foreach ($data['items'] as $row) {
            $oi = $orderItems->get($row['order_item_id']);
            if (!$oi) { $violations[] = ['order_item_id' => $row['order_item_id'], 'message' => 'item not in order']; continue; }
            $delivered = (int) ($oi->qty_approved ?? 0);
            $recv = (int) $row['received_qty'];
            if ($recv > $delivered) {
                $violations[] = ['order_item_id' => $oi->id, 'message' => 'received exceeds delivered', 'delivered' => $delivered, 'received' => $recv];
            }
            if ($row['status'] === 'not_received') {
                $c = trim((string) ($row['comment'] ?? ''));
                if ($c === '') {
                    $violations[] = ['order_item_id' => $oi->id, 'message' => 'comment required for not_received'];
                }
            }
        }

        if (!empty($violations)) {
            return response()->json(['message' => 'Validation failed', 'violations' => $violations], 422);
        }

        return DB::transaction(function () use ($order, $userId, $data, $orderItems) {
            $ack = \App\Models\Acknowledgement::create([
                'order_id' => $order->id,
                'user_id' => $userId,
                'status' => 'acknowledged',
                'comments' => $data['remarks'] ?? null,
                'employee_code' => $data['employee_code'],
                'receiver_name' => $data['receiver_name'],
                'branch_manager_name' => $data['branch_manager_name'],
                'remarks' => $data['remarks'] ?? null,
                'rating' => $data['rating'] ?? null,
            ]);

            $allFull = true; $anyPartial = false; $anyNotReceived = false;
            foreach ($data['items'] as $row) {
                $oi = $orderItems->get($row['order_item_id']);
                $delivered = (int) ($oi->qty_approved ?? 0);
                $recv = (int) $row['received_qty'];
                \App\Models\AcknowledgementItem::create([
                    'acknowledgement_id' => $ack->id,
                    'order_item_id' => $oi->id,
                    'received_qty' => $recv,
                    'status' => $row['status'],
                    'comment' => $row['comment'] ?? null,
                ]);
                if ($row['status'] === 'not_received') { $anyNotReceived = true; $allFull = false; }
                if ($recv < $delivered) { $anyPartial = true; $allFull = false; }
            }

            if ($anyNotReceived) {
                $order->status = 'issue_reported';
            } elseif ($allFull) {
                $order->status = 'completed';
            } else {
                $order->status = 'partially_received';
            }
            $order->save();

            Log::info('Order acknowledgement submitted', [
                'order_id' => $order->id,
                'user_id' => $userId,
                'status' => $order->status,
            ]);

            return response()->json($order->load(['acknowledgements.items']));
        });
    }

    public function reportIssue(Request $request, int $id)
    {
        $user = $request->attributes->get('kc_user');
        $userId = $user['sub'] ?? null;

        $order = Order::findOrFail($id);
        if ($order->user_id !== $userId) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        if ($order->status !== 'delivered') {
            return response()->json(['message' => 'Order must be delivered to report an issue'], 422);
        }

        $data = $request->validate([
            'description' => 'required|string',
        ]);

        return DB::transaction(function () use ($order, $userId, $data) {
            \App\Models\Issue::create([
                'order_id' => $order->id,
                'user_id' => $userId,
                'description' => $data['description'],
                'status' => 'open',
            ]);

            \App\Models\Acknowledgement::create([
                'order_id' => $order->id,
                'user_id' => $userId,
                'status' => 'issue_reported',
                'comments' => null,
            ]);

            $order->status = 'issue_reported';
            $order->save();
            Log::info('Order issue reported', ['order_id' => $order->id, 'user_id' => $userId]);
            return response()->json($order->fresh());
        });
    }

    public function issues(Request $request, int $id)
    {
        $user = $request->attributes->get('kc_user');
        $userId = $user['sub'] ?? null;
        $roles = $request->attributes->get('kc_roles', []);

        $query = Order::with('issues')->where('id', $id);
        if (!in_array('approver', $roles, true) && !in_array('vendor', $roles, true)) {
            $query->where('user_id', $userId);
        }
        $order = $query->firstOrFail();
        return response()->json($order->issues);
    }

    public function fullDetails(Request $request, int $id)
    {
        $user = $request->attributes->get('kc_user');
        $userId = $user['sub'] ?? null;
        $roles = $request->attributes->get('kc_roles', []);

        $orderQuery = Order::with(['items.product', 'acknowledgements.items', 'issues', 'shipment.items']);
        if (!in_array('approver', $roles, true) && !in_array('vendor', $roles, true) && !in_array('admin', $roles, true) && !in_array('super_admin', $roles, true)) {
            $orderQuery->where('user_id', $userId);
        }
        $order = $orderQuery->findOrFail($id);

        // Aggregate acknowledgement items by order_item_id
        $ackItems = collect();
        foreach ($order->acknowledgements as $ack) {
            foreach ($ack->items as $it) {
                $ackItems->push([
                    'order_item_id' => $it->order_item_id,
                    'received_qty' => (int) $it->received_qty,
                    'status' => $it->status,
                    'comment' => $it->comment,
                    'created_at' => $it->created_at,
                ]);
            }
        }
        $receivedByItem = $ackItems->groupBy('order_item_id')->map(function ($group) {
            $sum = $group->sum('received_qty');
            // latest by created_at for status/comment
            $latest = $group->sortByDesc('created_at')->first();
            return [
                'received_qty' => (int) $sum,
                'status' => $latest['status'] ?? null,
                'comment' => $latest['comment'] ?? null,
            ];
        });

        $items = [];
        // delivered per item via shipment_items sum
        $deliveredByItem = collect(optional($order->shipment)->items ?? [])->groupBy('order_item_id')->map(fn($g) => (int)$g->sum('delivered_qty'));

        foreach ($order->items as $it) {
            $r = $receivedByItem->get($it->id, ['received_qty' => 0, 'status' => null, 'comment' => null]);
            $delivered = (int) ($deliveredByItem->get($it->id) ?? 0);
            $items[] = [
                'product_name' => $it->product->name ?? '',
                'requested_qty' => (int) $it->qty_requested,
                'approved_qty' => (int) ($it->qty_approved ?? 0),
                'delivered_qty' => $delivered,
                'received_qty' => (int) ($r['received_qty'] ?? 0),
                'status' => $r['status'] ?? null,
                'comment' => $r['comment'] ?? null,
            ];
        }

        // Latest acknowledgement header
        $ackHeader = optional($order->acknowledgements->sortByDesc('id')->first());
        $ack = $ackHeader ? [
            'employee_code' => $ackHeader->employee_code,
            'receiver_name' => $ackHeader->receiver_name,
            'rating' => $ackHeader->rating,
            'remarks' => $ackHeader->remarks,
        ] : null;

        return response()->json([
            'order' => $order->only(['id','user_id','status','total_amount','created_at']),
            'items' => $items,
            'acknowledgement' => $ack,
            'issues' => $order->issues,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'address_id' => 'required|integer|exists:addresses,id',
        ]);

        $user = $request->attributes->get('kc_user');
        $userId = $user['sub'] ?? null;

        $itemsInput = $validated['items'];
        $addressId = $validated['address_id'];

        // Ensure address belongs to the current user
        $ownsAddress = \App\Models\Address::where('id', $addressId)->where('user_id', $userId)->exists();
        if (!$ownsAddress) {
            return response()->json(['message' => 'Invalid address'], 422);
        }

        return DB::transaction(function () use ($itemsInput, $userId, $addressId) {
            $order = new Order([
                'user_id' => $userId,
                'status' => 'pending_approval',
                'total_amount' => 0,
                'address_id' => $addressId,
            ]);
            $order->save();

            $total = 0;
            foreach ($itemsInput as $it) {
                $product = Product::findOrFail($it['product_id']);
                $qty = (int) $it['qty'];
                $price = $product->price; // snapshot unit price
                $total += $price * $qty;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'qty_requested' => $qty,
                    'price' => $price,
                ]);
            }

            $order->update(['total_amount' => $total]);

            Log::info('Order created', [
                'order_id' => $order->id,
                'user_id' => $userId,
                'total' => $total,
            ]);

            return response()->json($order->load(['items.product', 'address']), 201);
        });
    }

    public function approve(Request $request, int $id)
    {
        $roles = $request->attributes->get('kc_roles', []);
        if (!in_array('approver', $roles, true)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|integer|exists:order_items,id',
            'items.*.qty_approved' => 'required|integer|min:0',
            'comments' => 'nullable|string',
        ]);

        $order = Order::with('items')->findOrFail($id);
        if (!in_array($order->status, ['pending_approval','partially_approved'], true)) {
            return response()->json(['message' => 'Order is not eligible for approval'], 422);
        }

        $approver = $request->attributes->get('kc_user');
        $approverId = $approver['sub'] ?? null;

        return DB::transaction(function () use ($order, $data, $approverId) {
            $map = collect($data['items'])->keyBy('item_id');

            $violations = [];

            // Validate requested increments do not exceed remaining
            foreach ($order->items as $item) {
                if (!$map->has($item->id)) continue;
                $inc = max(0, (int) $map->get($item->id)['qty_approved']);
                $approvedSoFar = (int) ($item->qty_approved ?? 0);
                $remaining = max(0, (int) $item->qty_requested - $approvedSoFar);
                if ($inc > $remaining) {
                    $violations[] = [
                        'item_id' => $item->id,
                        'requested' => (int) $item->qty_requested,
                        'approved_so_far' => $approvedSoFar,
                        'remaining' => $remaining,
                        'attempt' => $inc,
                    ];
                }
            }

            if (!empty($violations)) {
                return response()->json([
                    'message' => 'qty_approved exceeds remaining for one or more items',
                    'violations' => $violations,
                ], 422);
            }

            // Apply increments cumulatively
            foreach ($order->items as $item) {
                if (!$map->has($item->id)) continue;
                $inc = max(0, (int) $map->get($item->id)['qty_approved']);
                $item->qty_approved = (int) ($item->qty_approved ?? 0) + $inc;
                // Ensure not beyond requested (safety)
                if ($item->qty_approved > $item->qty_requested) {
                    $item->qty_approved = $item->qty_requested;
                }
                $item->save();
            }

            // Compute new order status by remaining quantities
            $allZeroCumulative = true; // all items approved_so_far == 0
            $allSatisfied = true;      // all items remaining == 0

            foreach ($order->items as $item) {
                $approvedSoFar = (int) ($item->qty_approved ?? 0);
                $remaining = max(0, (int) $item->qty_requested - $approvedSoFar);
                if ($approvedSoFar > 0) $allZeroCumulative = false;
                if ($remaining > 0) $allSatisfied = false;
            }

            $newStatus = 'partially_approved';
            if ($allSatisfied) $newStatus = 'approved';
            if ($allZeroCumulative) $newStatus = 'rejected';

            $order->status = $newStatus;
            $order->save();

            \App\Models\Approval::create([
                'order_id' => $order->id,
                'approver_id' => $approverId ?? 'unknown',
                'status' => $newStatus === 'partially_approved' ? 'partial' : $newStatus,
                'comments' => $data['comments'] ?? null,
            ]);

            return response()->json($order->load(['items.product', 'address', 'approvals']));
        });
    }
}
