<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePortalJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->isClientViewer() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $wizard = session('portal.job_wizard', []);

        $merge = [];
        if (! $this->has('kit_item_ids') && isset($wizard['kit_item_ids'])) {
            $merge['kit_item_ids'] = $wizard['kit_item_ids'];
        }
        if (! $this->filled('drop_off_at') && isset($wizard['drop_off_at'])) {
            $merge['drop_off_at'] = $wizard['drop_off_at'];
        }
        if (! $this->filled('notes') && isset($wizard['notes'])) {
            $merge['notes'] = $wizard['notes'];
        }

        if (! empty($merge)) {
            $this->merge($merge);
        }
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $clientId = auth()->user()->client_id;
        $maxDate = now()->addWeeks(4)->toDateString();

        return [
            'kit_item_ids' => ['required', 'array', 'min:1'],
            'kit_item_ids.*' => [
                'integer',
                Rule::exists('kit_items', 'id')->where(function ($query) use ($clientId) {
                    $query->where('client_id', $clientId)
                        ->where('status', '!=', 'retired');
                }),
            ],
            'drop_off_at' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:'.$maxDate],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'kit_item_ids.required' => 'Please select at least one item to inspect.',
            'kit_item_ids.min' => 'Please select at least one item to inspect.',
            'kit_item_ids.*.exists' => 'One or more selected items are not available for inspection.',
            'drop_off_at.before_or_equal' => 'Drop-off date must be within the next 4 weeks.',
        ];
    }
}
