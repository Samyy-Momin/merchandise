<?php

declare(strict_types=1);

namespace App\Http\Requests\Sku;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSkuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $skuId = (int) $this->route('id');

        return [
            'sku_code' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('merchandise_skus', 'sku_code')
                    ->where(fn ($query) => $query->where('company_id', (int) $this->attributes->get('company_id')))
                    ->ignore($skuId),
            ],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'unit_price_cents' => ['sometimes', 'integer', 'min:1'],
            'stock_quantity' => ['sometimes', 'integer', 'min:0'],
            'images' => ['nullable', 'array'],
            'images.*' => ['string'],
        ];
    }
}
