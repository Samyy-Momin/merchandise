<?php

declare(strict_types=1);

namespace App\Models;

use CubeOneBiz\Tenant\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MerchandiseSku extends Model
{
    use BelongsToCompany;
    use HasFactory;

    const CREATED_AT = 'created_date';

    const UPDATED_AT = 'updated_date';

    protected $fillable = [
        'company_id',
        'catalogue_product_id',
        'sku_code',
        'name',
        'description',
        'category',
        'unit_price_cents',
        'stock_quantity',
        'images',
        'is_active',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'catalogue_product_id' => 'integer',
        'unit_price_cents' => 'integer',
        'stock_quantity' => 'integer',
        'images' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Whether this SKU is linked to a catalogue-service product.
     */
    public function isLinkedToCatalogue(): bool
    {
        return $this->catalogue_product_id !== null;
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(MerchandiseOrderItem::class, 'sku_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeLinkedToCatalogue(Builder $query): Builder
    {
        return $query->whereNotNull('catalogue_product_id');
    }

    public function scopeStandalone(Builder $query): Builder
    {
        return $query->whereNull('catalogue_product_id');
    }
}
