<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePortalKitItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->isClientViewer() ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $clientId = auth()->user()->client_id;

        return [
            'kit_type_id' => ['nullable', 'exists:kit_types,id', 'required_without:custom_type_name'],
            'custom_type_name' => ['nullable', 'string', 'max:100', 'required_without:kit_type_id'],
            'asset_tag' => ['nullable', 'string', 'max:100'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:100'],
            'asset_tag_prefix' => ['nullable', 'string', 'max:80'],
            'asset_tag_start' => ['nullable', 'integer', 'min:0'],
            'asset_tag_padding' => ['nullable', 'integer', 'min:1', 'max:6'],
            'serial_no' => ['nullable', 'string', 'max:100'],
            'manufacturer' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'lifting_people' => ['nullable', 'boolean'],
            'kit_group_id' => [
                'nullable',
                'integer',
                Rule::exists('kit_groups', 'id')->where('client_id', $clientId),
            ],
            'new_group_name' => ['nullable', 'string', 'max:100'],
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
