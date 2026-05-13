<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MarkInvoicePaidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'paid_at' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
