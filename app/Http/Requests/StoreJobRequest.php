<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->isAdmin() || auth()->user()?->isInspector();
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        $clientId = $this->integer('client_id') ?: null;

        return [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'kit_item_ids' => ['nullable', 'array'],
            'kit_item_ids.*' => [
                'integer',
                Rule::exists('kit_items', 'id')->where('client_id', $clientId),
            ],
            'condition_notes' => ['nullable', 'array'],
            'condition_notes.*' => ['nullable', 'string', 'max:500'],
        ];
    }
}
