<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AcknowledgementStatus;
use CubeOneBiz\Tenant\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchandiseAcknowledgement extends Model
{
    use BelongsToCompany;
    use HasFactory;

    const CREATED_AT = 'created_date';

    const UPDATED_AT = 'updated_date';

    protected $fillable = [
        'company_id',
        'order_id',
        'acknowledged_by',
        'acknowledged_at',
        'notes',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'order_id' => 'integer',
        'acknowledged_by' => 'integer',
        'acknowledged_at' => 'datetime',
        'status' => AcknowledgementStatus::class,
        'reviewed_by' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(MerchandiseOrder::class, 'order_id');
    }
}
