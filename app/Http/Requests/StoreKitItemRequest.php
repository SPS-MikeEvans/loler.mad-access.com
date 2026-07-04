<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreKitItemRequest extends FormRequest
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
            'kit_type_id' => ['nullable', 'exists:kit_types,id', 'required_without:custom_type_name'],
            'custom_type_name' => ['nullable', 'string', 'max:100', 'required_without:kit_type_id'],
            'asset_tag' => ['nullable', 'string', 'max:100'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:100'],
            'asset_tag_prefix' => ['nullable', 'string', 'max:80'],
            'asset_tag_start' => ['nullable', 'integer', 'min:0'],
            'asset_tag_padding' => ['nullable', 'integer', 'min:1', 'max:6'],
            'manufacturer' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'serial_no' => ['nullable', 'string', 'max:100'],
            'purchase_date' => ['nullable', 'date'],
            'first_use_date' => ['nullable', 'date'],
            'swl_kg' => ['nullable', 'integer', 'min:0'],
            'lifting_people' => ['nullable', 'boolean'],
            'status' => ['nullable', 'in:in_service,inspection_due,quarantined,retired'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'kit_type_id.required_without' => 'Please select an equipment type or enter a custom name.',
            'custom_type_name.required_without' => 'Please select an equipment type or enter a custom name.',
        ];
    }
}
