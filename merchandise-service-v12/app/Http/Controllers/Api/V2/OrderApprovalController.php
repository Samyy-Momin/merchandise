<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\DTOs\ApprovalDTO;
use App\Http\Requests\Approval\ApproveOrderRequest;
use App\Services\Approval\ApprovalServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class OrderApprovalController extends Controller
{
    public function __construct(
        private readonly ApprovalServiceInterface $approvalService,
    ) {}

    public function approve(ApproveOrderRequest $request, int $id): JsonResponse
    {
        $staffId = (int) $request->header('X-User-ID');
        $dto = ApprovalDTO::fromArray($request->validated(), $staffId);
        $order = $this->approvalService->approve($id, $dto);

        return response()->json(['data' => $order]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $staffId = (int) $request->header('X-User-ID');
        $validated = $request->validate([
            'reason' => ['required', 'string'],
        ]);
        $order = $this->approvalService->reject($id, $staffId, $validated['reason']);

        return response()->json(['data' => $order]);
    }
}
