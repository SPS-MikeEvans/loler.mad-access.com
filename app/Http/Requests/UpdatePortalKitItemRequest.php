<?php

namespace App\Http\Requests;

use App\Models\KitItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePortalKitItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $kitItem = $this->route('kitItem');

        return $kitItem instanceof KitItem
            && (auth()->user()?->can('manage-own-kit', $kitItem) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $kitItem = $this->route('kitItem');

        if ($kitItem instanceof KitItem && $kitItem->hasInspections()) {
            foreach (KitItem::LOCKED_FIELDS_AFTER_INSPECTION as $field) {
                $this->offsetUnset($field);
            }
        }
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $kitItem = $this->route('kitItem');
        $clientId = auth()->user()->client_id;
        $locked = $kitItem instanceof KitItem && $kitItem->hasInspections();

        $rules = [
            'custom_type_name' => ['nullable', 'string', 'max:100'],
            'asset_tag' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('kit_items', 'asset_tag')->ignore($kitItem?->id),
            ],
            'swl_kg' => ['nullable', 'integer', 'min:0'],
            'lifting_people' => ['nullable', 'boolean'],
            'flag_notes' => ['nullable', 'string', 'max:1000'],
            'kit_group_id' => [
                'nullable',
                'integer',
                Rule::exists('kit_groups', 'id')->where('client_id', $clientId),
            ],
        ];

        if (! $locked) {
            $rules['kit_type_id'] = ['nullable', 'integer', 'exists:kit_types,id'];
            $rules['manufacturer'] = ['nullable', 'string', 'max:100'];
            $rules['model'] = ['nullable', 'string', 'max:100'];
            $rules['serial_no'] = ['nullable', 'string', 'max:100'];
            $rules['purchase_date'] = ['nullable', 'date'];
            $rules['first_use_date'] = ['nullable', 'date'];
        }

        return $rules;
    }
}
