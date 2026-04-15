<?php

declare(strict_types=1);

namespace App\Services\Approval;

use App\Models\ApprovalRequest;

interface ApprovalWorkflowServiceInterface
{
    /**
     * @param  array{
     *   company_id:int,
     *   entity_type:string,
     *   entity_id:int,
     *   buyer_store_id?:int|null,
     *   vendor_store_id?:int|null,
     *   approver_role?:string|null,
     *   requested_by?:int|null
     * }  $data
     */
    public function createDraft(array $data): ApprovalRequest;

    /**
     * @param  array{
     *   company_id:int,
     *   entity_type:string,
     *   entity_id:int,
     *   buyer_store_id:int|null,
     *   vendor_store_id:int|null,
     *   requested_by:int|null,
     *   approver_role:string|null
     * }  $data
     */
    public function createPending(array $data): ApprovalRequest;

    public function submit(int $approvalId): ApprovalRequest;

    public function approve(int $approvalId, int $approverId, ?string $reason = null): ApprovalRequest;

    public function reject(int $approvalId, int $approverId, string $reason): ApprovalRequest;
}
