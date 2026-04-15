<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceStatus;
use CubeOneBiz\Tenant\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MerchandiseInvoice extends Model
{
    use BelongsToCompany;
    use HasFactory;

    const CREATED_AT = 'created_date';

    const UPDATED_AT = 'updated_date';

    protected $fillable = [
        'company_id',
        'invoice_number',
        'order_id',
        'customer_id',
        'status',
        'subtotal_cents',
        'tax_cents',
        'discount_cents',
        'total_cents',
        'amount_paid_cents',
        'due_date',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'order_id' => 'integer',
        'customer_id' => 'integer',
        'status' => InvoiceStatus::class,
        'subtotal_cents' => 'integer',
        'tax_cents' => 'integer',
        'discount_cents' => 'integer',
        'total_cents' => 'integer',
        'amount_paid_cents' => 'integer',
        'due_date' => 'date',
    ];

    protected $appends = [
        'amount_due_cents',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(MerchandiseOrder::class, 'order_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(MerchandisePayment::class, 'invoice_id');
    }

    public function getAmountDueCentsAttribute(): int
    {
        return max(0, $this->total_cents - $this->amount_paid_cents);
    }

    public function isOverdue(): bool
    {
        return $this->due_date->isPast() && $this->amount_paid_cents < $this->total_cents;
    }
}
