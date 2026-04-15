<?php

declare(strict_types=1);

namespace App\Models;

use CubeOneBiz\Tenant\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchandisePayment extends Model
{
    use BelongsToCompany;
    use HasFactory;

    const CREATED_AT = 'created_date';

    const UPDATED_AT = 'updated_date';

    protected $fillable = [
        'company_id',
        'invoice_id',
        'amount_cents',
        'payment_method',
        'reference',
        'paid_at',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'invoice_id' => 'integer',
        'amount_cents' => 'integer',
        'paid_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(MerchandiseInvoice::class, 'invoice_id');
    }
}
