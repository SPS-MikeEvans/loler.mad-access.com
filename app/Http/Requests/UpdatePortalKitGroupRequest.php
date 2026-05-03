<?php

namespace App\Http\Requests;

use App\Models\KitGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePortalKitGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        $kitGroup = $this->route('kit_group');

        return $kitGroup instanceof KitGroup
            && (auth()->user()?->can('manage-own-kit-group', $kitGroup) ?? false);
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
