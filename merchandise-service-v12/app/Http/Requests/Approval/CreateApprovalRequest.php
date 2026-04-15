<?php

declare(strict_types=1);

namespace App\Http\Requests\Approval;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'entity_type' => ['required', 'string', Rule::in(['merchandise_order'])],
            'entity_id' => ['required', 'integer', 'min:1'],
            'buyer_store_id' => ['nullable', 'integer', 'min:1'],
            'vendor_store_id' => ['nullable', 'integer', 'min:1'],
            'approver_role' => ['nullable', 'string', Rule::in(['admin', 'senior_manager', 'super_admin'])],
        ];
    }
}
