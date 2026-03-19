<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreKitTypeRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:100'],
            'interval_months' => ['required', 'integer', 'min:1', 'max:120'],
            'lifts_people' => ['nullable', 'boolean'],
            'swl_description' => ['nullable', 'string', 'max:200'],
            'inspection_price' => ['nullable', 'numeric', 'min:0'],
            'instructions' => ['nullable', 'string'],
            'resources_links' => ['nullable', 'array'],
            'resources_links.*.name' => ['nullable', 'string', 'max:200'],
            'resources_links.*.url' => ['nullable', 'string', 'max:500'],
            'checklist_json' => ['nullable', 'array'],
            'checklist_json.*.category' => ['nullable', 'string', 'max:100'],
            'checklist_json.*.text' => ['nullable', 'string', 'max:500'],
            'spec_pdf_path' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'inspection_pdf_path' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }
}
