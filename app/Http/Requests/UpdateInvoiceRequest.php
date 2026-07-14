<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
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
            'issued_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issued_date'],
            'period_from' => ['required', 'date'],
            'period_to' => ['required', 'date', 'after_or_equal:period_from'],
            'notes' => ['nullable', 'string'],
            'discount_percent' => ['nullable', 'numeric', 'gt:0', 'lt:100'],
        ];
    }
}
