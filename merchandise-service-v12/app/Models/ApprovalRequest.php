<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ApprovalRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalRequest extends Model
{
    /** @use HasFactory<ApprovalRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'entity_type',
        'entity_id',
        'status',
        'buyer_store_id',
        'vendor_store_id',
        'approver_role',
        'requested_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'decision_reason',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'entity_id' => 'integer',
        'buyer_store_id' => 'integer',
        'vendor_store_id' => 'integer',
        'requested_by' => 'integer',
        'approved_by' => 'integer',
        'rejected_by' => 'integer',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function actions(): HasMany
    {
        return $this->hasMany(ApprovalAction::class, 'approval_request_id');
    }
}
