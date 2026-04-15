<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Http\Requests\Approval\CreateApprovalRequest;
use App\Http\Requests\Approval\ReviewApprovalRequest;
use App\Services\Approval\ApprovalWorkflowServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class ApprovalWorkflowController extends Controller
{
    public function __construct(
        private readonly ApprovalWorkflowServiceInterface $approvalWorkflowService,
    ) {}

    public function store(CreateApprovalRequest $request): JsonResponse
    {
        $companyId = (int) ($request->attributes->get('company_id') ?? 1);
        $requestedBy = (int) $request->header('X-User-ID');
        $payload = $request->validated();

        $approvalRequest = $this->approvalWorkflowService->createDraft([
            'company_id' => $companyId,
            'entity_type' => $payload['entity_type'],
            'entity_id' => (int) $payload['entity_id'],
            'buyer_store_id' => $payload['buyer_store_id'] ?? null,
            'vendor_store_id' => $payload['vendor_store_id'] ?? null,
            'approver_role' => $payload['approver_role'] ?? 'admin',
            'requested_by' => $requestedBy,
        ]);

        return response()->json(['data' => $approvalRequest], 201);
    }

    public function submit(int $id): JsonResponse
    {
        $approvalRequest = $this->approvalWorkflowService->submit($id);

        return response()->json(['data' => $approvalRequest]);
    }

    public function approve(ReviewApprovalRequest $request, int $id): JsonResponse
    {
        $approverId = (int) $request->header('X-User-ID');
        $approvalRequest = $this->approvalWorkflowService->approve(
            approvalId: $id,
            approverId: $approverId,
            reason: $request->validated('reason'),
        );

        return response()->json(['data' => $approvalRequest]);
    }

    public function reject(ReviewApprovalRequest $request, int $id): JsonResponse
    {
        $approverId = (int) $request->header('X-User-ID');
        $approvalRequest = $this->approvalWorkflowService->reject(
            approvalId: $id,
            approverId: $approverId,
            reason: $request->validated('reason'),
        );

        return response()->json(['data' => $approvalRequest]);
    }
}
