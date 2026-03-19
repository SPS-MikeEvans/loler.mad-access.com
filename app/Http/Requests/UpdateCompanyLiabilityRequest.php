<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyLiabilityRequest extends FormRequest
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
            'terms_and_conditions' => ['nullable', 'string'],
            'insurances' => ['nullable', 'array'],
            'insurances.*.name' => ['nullable', 'string', 'max:200'],
            'insurances.*.insurer' => ['nullable', 'string', 'max:200'],
            'insurances.*.policy_number' => ['nullable', 'string', 'max:200'],
            'insurances.*.expiry' => ['nullable', 'date'],
            'insurances.*.limit' => ['nullable', 'numeric', 'min:0'],
            'insurances.*.certificate' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'insurances.*.existing_certificate_path' => ['nullable', 'string', 'max:500'],
            'insurances.*.remove_certificate' => ['nullable', 'boolean'],
        ];
    }
}
