<?php

declare(strict_types=1);

namespace App\Models;

use CubeOneBiz\Tenant\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchandiseDispatch extends Model
{
    use BelongsToCompany;
    use HasFactory;

    const CREATED_AT = 'created_date';

    const UPDATED_AT = 'updated_date';

    protected $fillable = [
        'company_id',
        'order_id',
        'dispatched_by',
        'courier',
        'tracking_number',
        'dispatched_at',
        'estimated_delivery_at',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'order_id' => 'integer',
        'dispatched_by' => 'integer',
        'dispatched_at' => 'datetime',
        'estimated_delivery_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(MerchandiseOrder::class, 'order_id');
    }
}
