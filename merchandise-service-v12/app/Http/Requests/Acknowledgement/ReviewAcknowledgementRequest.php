<?php

declare(strict_types=1);

namespace App\Http\Requests\Acknowledgement;

use Illuminate\Foundation\Http\FormRequest;

class ReviewAcknowledgementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string'],
        ];
    }
}
