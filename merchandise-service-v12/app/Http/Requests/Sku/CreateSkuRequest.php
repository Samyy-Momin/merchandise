<?php

declare(strict_types=1);

namespace App\Http\Requests\Sku;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateSkuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('merchandise_skus', 'sku_code')
                    ->where(fn ($query) => $query->where('company_id', (int) $this->attributes->get('company_id'))),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'unit_price_cents' => ['required', 'integer', 'min:1'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'images' => ['nullable', 'array'],
            'images.*' => ['string'],
        ];
    }
}
