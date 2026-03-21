<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePortalKitItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->isClientViewer() ?? false;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'kit_type_id' => ['required', 'exists:kit_types,id'],
            'asset_tag' => ['nullable', 'string', 'max:100', 'unique:kit_items,asset_tag'],
            'serial_no' => ['nullable', 'string', 'max:100'],
            'manufacturer' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'lifting_people' => ['nullable', 'boolean'],
        ];
    }
}
