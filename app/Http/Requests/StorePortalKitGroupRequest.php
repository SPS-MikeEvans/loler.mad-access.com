<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePortalKitGroupRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'kit_item_ids' => ['array'],
            'kit_item_ids.*' => [
                'integer',
                Rule::exists('kit_items', 'id')->where('client_id', $clientId),
            ],
        ];
    }
}
