<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
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
            'period_from' => ['required', 'date'],
            'period_to' => ['required', 'date', 'after_or_equal:period_from'],
            'notes' => ['nullable', 'string'],
            'waived_inspection_ids' => ['nullable', 'array'],
            'waived_inspection_ids.*' => ['integer'],
            'discount_percent' => ['nullable', 'numeric', 'gt:0', 'lt:100'],
            'fixed_total' => ['nullable', 'numeric', 'gte:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if (filled($this->input('discount_percent')) && filled($this->input('fixed_total'))) {
                $v->errors()->add('discount_percent', 'Use either a discount % or a fixed total, not both.');
            }
        });
    }
}
