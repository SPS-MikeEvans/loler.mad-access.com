<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkEditKitTypeRequest extends FormRequest
{
    public const ACTIONS = [
        'set_price',
        'adjust_price_amount',
        'adjust_price_percent',
        'add_resource_link',
        'remove_resource_link',
        'set_interval_months',
        'set_lifts_people',
    ];

    public function authorize(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $base = [
            'action' => ['required', 'string', 'in:'.implode(',', self::ACTIONS)],
            'kit_type_ids' => ['required', 'array', 'min:1'],
            'kit_type_ids.*' => ['integer', 'exists:kit_types,id'],
        ];

        return match ($this->input('action')) {
            'set_price' => $base + ['value' => ['required', 'numeric', 'min:0', 'max:100000']],
            'adjust_price_amount' => $base + ['value' => ['required', 'numeric']],
            'adjust_price_percent' => $base + ['value' => ['required', 'numeric', 'min:-100', 'max:1000']],
            'add_resource_link' => $base + [
                'link_name' => ['required', 'string', 'max:200'],
                'link_url' => ['required', 'url', 'max:1000'],
            ],
            'remove_resource_link' => $base + ['link_url' => ['required', 'string', 'max:1000']],
            'set_interval_months' => $base + ['value' => ['required', 'integer', 'min:1', 'max:120']],
            'set_lifts_people' => $base + ['value' => ['required', 'boolean']],
            default => $base,
        };
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'kit_type_ids.required' => 'Select at least one kit type to apply the bulk edit to.',
            'kit_type_ids.min' => 'Select at least one kit type to apply the bulk edit to.',
            'value.required' => 'A value is required for this action.',
            'link_name.required' => 'Provide a label for the new link.',
            'link_url.required' => 'Provide a URL.',
        ];
    }
}
