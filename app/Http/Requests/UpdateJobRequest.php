<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->isAdmin() || auth()->user()?->isInspector();
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:2000'],
            'kit_item_ids' => ['nullable', 'array'],
            'kit_item_ids.*' => ['integer', 'exists:kit_items,id'],
            'condition_notes' => ['nullable', 'array'],
            'condition_notes.*' => ['nullable', 'string', 'max:500'],
        ];
    }
}
