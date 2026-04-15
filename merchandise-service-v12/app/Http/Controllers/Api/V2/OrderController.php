<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\DTOs\OrderDTO;
use App\Http\Requests\Order\PlaceOrderRequest;
use App\Services\Order\OrderServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderServiceInterface $orderService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status']);
        $userId = (int) $request->header('X-User-ID');
        $role = $request->header('X-User-Role');

        if ($role === 'customer') {
            $filters['customer_id'] = $userId;
        }

        $orders = $this->orderService->listOrders($filters);

        return response()->json([
            'data' => $orders->items(),
            'meta' => [
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
                'current_page' => $orders->currentPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $order = $this->orderService->findOrFail($id);

        return response()->json(['data' => $order]);
    }

    public function store(PlaceOrderRequest $request): JsonResponse
    {
        $customerId = (int) $request->header('X-User-ID');
        $companyId = (int) ($request->attributes->get('company_id') ?? 1);

        $dto = OrderDTO::fromArray($request->validated(), $customerId, $companyId);
        $order = $this->orderService->placeOrder($dto);

        return response()->json(['data' => $order], 201);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $customerId = (int) $request->header('X-User-ID');
        $this->orderService->cancelOrder($id, $customerId);

        return response()->json(['message' => 'Order cancelled.']);
    }
}
