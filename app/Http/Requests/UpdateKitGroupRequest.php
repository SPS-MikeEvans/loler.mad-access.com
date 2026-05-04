<?php

namespace App\Http\Requests;

use App\Models\Client;
use App\Models\KitGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKitGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        $client = $this->route('client');
        $kitGroup = $this->route('kit_group');

        return ($this->user()?->isAdmin() || $this->user()?->isInspector())
            && $client instanceof Client
            && $kitGroup instanceof KitGroup
            && $kitGroup->client_id === $client->id;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        /** @var Client $client */
        $client = $this->route('client');

        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'kit_item_ids' => ['array'],
            'kit_item_ids.*' => [
                'integer',
                Rule::exists('kit_items', 'id')->where('client_id', $client->id),
            ],
        ];
    }
}
