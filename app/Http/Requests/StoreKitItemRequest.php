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
            'kit_type_id' => ['required', 'exists:kit_types,id'],
            'asset_tag' => ['nullable', 'string', 'max:100', 'unique:kit_items,asset_tag'],
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
}
