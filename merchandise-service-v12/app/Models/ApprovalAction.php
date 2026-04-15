<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ApprovalActionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalAction extends Model
{
    /** @use HasFactory<ApprovalActionFactory> */
    use HasFactory;

    protected $fillable = [
        'approval_request_id',
        'action',
        'actor_id',
        'actor_role',
        'reason',
    ];

    protected $casts = [
        'approval_request_id' => 'integer',
        'actor_id' => 'integer',
    ];

    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class, 'approval_request_id');
    }
}
