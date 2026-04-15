<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Services\Acknowledgement\AcknowledgementServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AcknowledgementController extends Controller
{
    public function __construct(
        private readonly AcknowledgementServiceInterface $ackService,
    ) {}

    public function acknowledge(Request $request, int $id): JsonResponse
    {
        $customerId = (int) $request->header('X-User-ID');
        $ack = $this->ackService->acknowledge($id, $customerId, $request->input('notes'));

        return response()->json(['data' => $ack], 201);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $staffId = (int) $request->header('X-User-ID');
        $ack = $this->ackService->approveAcknowledgement($id, $staffId);

        return response()->json(['data' => $ack]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $staffId = (int) $request->header('X-User-ID');
        $validated = $request->validate([
            'reason' => ['required', 'string'],
        ]);
        $ack = $this->ackService->rejectAcknowledgement($id, $staffId, $validated['reason']);

        return response()->json(['data' => $ack]);
    }
}
