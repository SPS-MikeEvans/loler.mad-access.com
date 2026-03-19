<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreInspectionRequest extends FormRequest
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
            'inspection_date' => ['required', 'date'],
            'next_due_date' => ['required', 'date', 'after:inspection_date'],
            'overall_status' => ['required', 'in:pass,fail,conditional'],
            'report_notes' => ['nullable', 'string'],
            'digital_sig_path' => ['nullable', 'image', 'max:2048'],
            'checks' => ['required', 'array'],
            'checks.*.check_category' => ['required', 'string'],
            'checks.*.check_text' => ['required', 'string'],
            'checks.*.status' => ['required', 'in:pass,fail,n/a'],
            'checks.*.notes' => ['nullable', 'string'],
            'checks.*.photo'         => ['nullable', 'image', 'max:5120'],
            'checks.*.upload_token'  => ['nullable', 'string', 'size:36'],
        ];
    }
}
