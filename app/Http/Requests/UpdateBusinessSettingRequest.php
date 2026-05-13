<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessSettingRequest extends FormRequest
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
            'bank_name' => ['nullable', 'string', 'max:120'],
            'account_holder' => ['nullable', 'string', 'max:120'],
            'sort_code' => ['nullable', 'string', 'regex:/^\d{2}-?\d{2}-?\d{2}$/'],
            'account_number' => ['nullable', 'string', 'regex:/^\d{8}$/'],
            'iban' => ['nullable', 'string', 'max:34'],
            'reference_instructions' => ['nullable', 'string', 'max:500'],
            'payment_terms_days' => ['required', 'integer', 'between:0,120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sort_code.regex' => 'Sort code must be 6 digits (with or without dashes), e.g. 12-34-56 or 123456.',
            'account_number.regex' => 'Account number must be exactly 8 digits.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($sortCode = $this->input('sort_code')) {
            $this->merge([
                'sort_code' => preg_replace('/[^\d]/', '', (string) $sortCode),
            ]);
        }
    }
}
