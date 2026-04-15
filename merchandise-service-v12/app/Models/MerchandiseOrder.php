<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use CubeOneBiz\Tenant\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MerchandiseOrder extends Model
{
    use BelongsToCompany;
    use HasFactory;

    const CREATED_AT = 'created_date';

    const UPDATED_AT = 'updated_date';

    protected $fillable = [
        'company_id',
        'order_ref',
        'customer_id',
        'order_kind',
        'buyer_store_id',
        'vendor_store_id',
        'fulfillment_store_id',
        'status',
        'subtotal_cents',
        'total_cents',
        'notes',
        'approved_by',
        'approved_at',
        'rejected_reason',
        'approval_request_id',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'buyer_store_id' => 'integer',
        'vendor_store_id' => 'integer',
        'fulfillment_store_id' => 'integer',
        'approval_request_id' => 'integer',
        'status' => OrderStatus::class,
        'subtotal_cents' => 'integer',
        'total_cents' => 'integer',
        'customer_id' => 'integer',
        'approved_by' => 'integer',
        'approved_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(MerchandiseOrderItem::class, 'order_id');
    }

    public function dispatch(): HasOne
    {
        return $this->hasOne(MerchandiseDispatch::class, 'order_id');
    }

    public function acknowledgement(): HasOne
    {
        return $this->hasOne(MerchandiseAcknowledgement::class, 'order_id');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(MerchandiseInvoice::class, 'order_id');
    }

    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class, 'approval_request_id');
    }

    public function isApprovable(): bool
    {
        return $this->status === OrderStatus::PendingApproval;
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [OrderStatus::Submitted, OrderStatus::PendingApproval], true);
    }
}
