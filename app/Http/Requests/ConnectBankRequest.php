<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ConnectBankRequest extends FormRequest
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
            'institution_id' => ['required', 'string', 'max:80'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('institution_id')) {
            $this->merge(['institution_id' => (string) config('banking.gocardless.default_institution_id')]);
        }
    }
}
