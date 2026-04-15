<?php

declare(strict_types=1);

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_kind' => ['nullable', 'string', Rule::in(['standard', 'procurement'])],
            'buyer_store_id' => ['nullable', 'integer', 'min:1', 'required_if:order_kind,procurement'],
            'vendor_store_id' => ['nullable', 'integer', 'min:1', 'required_if:order_kind,procurement'],
            'fulfillment_store_id' => ['nullable', 'integer', 'min:1'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sku_id' => ['required', 'integer', 'min:1'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
