<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\DTOs\DispatchDTO;
use App\Services\Dispatch\DispatchServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DispatchController extends Controller
{
    public function __construct(
        private readonly DispatchServiceInterface $dispatchService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $dispatches = $this->dispatchService->listDispatches($request->only(['company_id']));

        return response()->json([
            'data' => $dispatches->items(),
            'meta' => [
                'total' => $dispatches->total(),
                'per_page' => $dispatches->perPage(),
                'current_page' => $dispatches->currentPage(),
            ],
        ]);
    }

    public function dispatch(Request $request, int $id): JsonResponse
    {
        $staffId = (int) $request->header('X-User-ID');
        $dto = DispatchDTO::fromArray($request->all(), $staffId);
        $dispatch = $this->dispatchService->dispatch($id, $dto);

        return response()->json(['data' => $dispatch], 201);
    }
}
