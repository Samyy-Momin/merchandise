<?php

declare(strict_types=1);

namespace App\Models;

use CubeOneBiz\Tenant\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchandiseOrderItem extends Model
{
    use BelongsToCompany;
    use HasFactory;

    const CREATED_AT = 'created_date';

    const UPDATED_AT = 'updated_date';

    protected $fillable = [
        'company_id',
        'order_id',
        'sku_id',
        'sku_code',
        'sku_name',
        'requested_quantity',
        'approved_quantity',
        'unit_price_cents',
        'line_total_cents',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'order_id' => 'integer',
        'sku_id' => 'integer',
        'requested_quantity' => 'integer',
        'approved_quantity' => 'integer',
        'unit_price_cents' => 'integer',
        'line_total_cents' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(MerchandiseOrder::class, 'order_id');
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(MerchandiseSku::class, 'sku_id');
    }
}
