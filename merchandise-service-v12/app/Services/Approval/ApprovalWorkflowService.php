<?php

declare(strict_types=1);

namespace App\Services\Approval;

use App\Exceptions\InvalidOrderTransitionException;
use App\Models\ApprovalAction;
use App\Models\ApprovalRequest;
use Illuminate\Support\Facades\DB;

class ApprovalWorkflowService implements ApprovalWorkflowServiceInterface
{
    public function createDraft(array $data): ApprovalRequest
    {
        return DB::transaction(function () use ($data): ApprovalRequest {
            $approvalRequest = ApprovalRequest::create([
                'company_id' => $data['company_id'],
                'entity_type' => $data['entity_type'],
                'entity_id' => $data['entity_id'],
                'status' => 'draft',
                'buyer_store_id' => $data['buyer_store_id'] ?? null,
                'vendor_store_id' => $data['vendor_store_id'] ?? null,
                'approver_role' => $data['approver_role'] ?? 'admin',
                'requested_by' => $data['requested_by'] ?? null,
            ]);

            $this->recordAction(
                approvalRequestId: (int) $approvalRequest->id,
                action: 'created',
                actorId: $data['requested_by'] ?? null,
                actorRole: null,
                reason: null,
            );

            return $approvalRequest->fresh();
        });
    }

    public function createPending(array $data): ApprovalRequest
    {
        return DB::transaction(function () use ($data): ApprovalRequest {
            $approvalRequest = ApprovalRequest::create([
                'company_id' => $data['company_id'],
                'entity_type' => $data['entity_type'],
                'entity_id' => $data['entity_id'],
                'status' => 'pending_approval',
                'buyer_store_id' => $data['buyer_store_id'],
                'vendor_store_id' => $data['vendor_store_id'],
                'approver_role' => $data['approver_role'] ?? 'admin',
                'requested_by' => $data['requested_by'] ?? null,
                'submitted_at' => now(),
            ]);

            $this->recordAction(
                approvalRequestId: (int) $approvalRequest->id,
                action: 'created',
                actorId: $data['requested_by'] ?? null,
                actorRole: null,
                reason: null,
            );
            $this->recordAction(
                approvalRequestId: (int) $approvalRequest->id,
                action: 'submitted',
                actorId: $data['requested_by'] ?? null,
                actorRole: null,
                reason: null,
            );

            return $approvalRequest->fresh();
        });
    }

    public function submit(int $approvalId): ApprovalRequest
    {
        return DB::transaction(function () use ($approvalId): ApprovalRequest {
            $approvalRequest = ApprovalRequest::query()->findOrFail($approvalId);

            if ($approvalRequest->status !== 'draft') {
                throw new InvalidOrderTransitionException(
                    "Cannot submit approval request in status [{$approvalRequest->status}]."
                );
            }

            $approvalRequest->update([
                'status' => 'pending_approval',
                'submitted_at' => now(),
            ]);

            $this->recordAction(
                approvalRequestId: (int) $approvalRequest->id,
                action: 'submitted',
                actorId: null,
                actorRole: null,
                reason: null,
            );

            return $approvalRequest->fresh();
        });
    }

    public function approve(int $approvalId, int $approverId, ?string $reason = null): ApprovalRequest
    {
        return DB::transaction(function () use ($approvalId, $approverId, $reason): ApprovalRequest {
            $approvalRequest = ApprovalRequest::query()->findOrFail($approvalId);

            if ($approvalRequest->status !== 'pending_approval') {
                throw new InvalidOrderTransitionException(
                    "Cannot approve approval request in status [{$approvalRequest->status}]."
                );
            }

            $approvalRequest->update([
                'status' => 'approved',
                'approved_by' => $approverId,
                'approved_at' => now(),
                'decision_reason' => $reason,
            ]);

            $this->recordAction(
                approvalRequestId: (int) $approvalRequest->id,
                action: 'approved',
                actorId: $approverId,
                actorRole: null,
                reason: $reason,
            );

            return $approvalRequest->fresh();
        });
    }

    public function reject(int $approvalId, int $approverId, string $reason): ApprovalRequest
    {
        return DB::transaction(function () use ($approvalId, $approverId, $reason): ApprovalRequest {
            $approvalRequest = ApprovalRequest::query()->findOrFail($approvalId);

            if ($approvalRequest->status !== 'pending_approval') {
                throw new InvalidOrderTransitionException(
                    "Cannot reject approval request in status [{$approvalRequest->status}]."
                );
            }

            $approvalRequest->update([
                'status' => 'rejected',
                'rejected_by' => $approverId,
                'rejected_at' => now(),
                'decision_reason' => $reason,
            ]);

            $this->recordAction(
                approvalRequestId: (int) $approvalRequest->id,
                action: 'rejected',
                actorId: $approverId,
                actorRole: null,
                reason: $reason,
            );

            return $approvalRequest->fresh();
        });
    }

    private function recordAction(
        int $approvalRequestId,
        string $action,
        ?int $actorId,
        ?string $actorRole,
        ?string $reason,
    ): void {
        ApprovalAction::create([
            'approval_request_id' => $approvalRequestId,
            'action' => $action,
            'actor_id' => $actorId,
            'actor_role' => $actorRole,
            'reason' => $reason,
        ]);
    }
}
