<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Bulk Edit Kit Types
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-3">
                    {{ $kitTypes->count() }} kit {{ Str::plural('type', $kitTypes->count()) }} selected
                </h3>
                <details class="text-sm text-gray-600">
                    <summary class="cursor-pointer text-brand-navy hover:text-brand-red">View selected list</summary>
                    <ul class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-y-1 gap-x-4 text-gray-700">
                        @foreach ($kitTypes as $type)
                            <li>{{ $type->name }} <span class="text-gray-400 text-xs">— {{ $type->brand ?? '—' }}</span></li>
                        @endforeach
                    </ul>
                </details>
            </div>

            <form method="POST" action="{{ route('kit-types.bulk-edit.preview') }}"
                  x-data="{ action: '{{ old('action', 'set_price') }}' }"
                  class="bg-white shadow-sm rounded-lg p-6 space-y-6">
                @csrf

                @foreach ($kitTypes as $type)
                    <input type="hidden" name="kit_type_ids[]" value="{{ $type->id }}">
                @endforeach

                @if ($errors->any())
                    <div class="px-4 py-3 bg-red-100 text-red-800 rounded-lg text-sm space-y-1">
                        @foreach ($errors->all() as $err)
                            <p>{{ $err }}</p>
                        @endforeach
                    </div>
                @endif

                <div>
                    <x-input-label for="action" :value="__('What do you want to change?')" />
                    <select id="action" name="action" x-model="action"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-brand-red focus:ring-brand-red">
                        <optgroup label="Inspection price">
                            <option value="set_price">Set price (absolute)</option>
                            <option value="adjust_price_amount">Adjust by amount (£)</option>
                            <option value="adjust_price_percent">Adjust by percentage (%)</option>
                        </optgroup>
                        <optgroup label="Resource links">
                            <option value="add_resource_link">Add a resource link</option>
                            <option value="remove_resource_link">Remove a resource link</option>
                        </optgroup>
                        <optgroup label="Other">
                            <option value="set_interval_months">Set inspection interval (months)</option>
                            <option value="set_lifts_people">Set 'lifts people' flag</option>
                        </optgroup>
                    </select>
                </div>

                {{-- Price (absolute) --}}
                <div x-show="action === 'set_price'" x-cloak>
                    <x-input-label for="value_set_price" :value="__('New price (£)')" />
                    <x-text-input id="value_set_price" type="number" step="0.01" min="0"
                                  name="value" :value="old('value')"
                                  ::disabled="action !== 'set_price'"
                                  x-bind:disabled="action !== 'set_price'"
                                  class="mt-1 block w-full sm:w-48" />
                    <p class="mt-1 text-xs text-gray-500">Sets the inspection price for every selected type to this value.</p>
                </div>

                {{-- Price adjust amount --}}
                <div x-show="action === 'adjust_price_amount'" x-cloak>
                    <x-input-label for="value_adjust_amount" :value="__('Amount (£) — use negative to decrease')" />
                    <x-text-input id="value_adjust_amount" type="number" step="0.01"
                                  name="value" :value="old('value')"
                                  x-bind:disabled="action !== 'adjust_price_amount'"
                                  class="mt-1 block w-full sm:w-48" />
                    <p class="mt-1 text-xs text-gray-500">e.g. <code>5.00</code> adds £5; <code>-2.50</code> subtracts £2.50. Prices are clamped to a minimum of £0.</p>
                </div>

                {{-- Price adjust percent --}}
                <div x-show="action === 'adjust_price_percent'" x-cloak>
                    <x-input-label for="value_adjust_percent" :value="__('Percentage — use negative to decrease')" />
                    <x-text-input id="value_adjust_percent" type="number" step="0.01" min="-100" max="1000"
                                  name="value" :value="old('value')"
                                  x-bind:disabled="action !== 'adjust_price_percent'"
                                  class="mt-1 block w-full sm:w-48" />
                    <p class="mt-1 text-xs text-gray-500">e.g. <code>10</code> adds 10%; <code>-5</code> reduces by 5%. Types without a price are set to £0.</p>
                </div>

                {{-- Add resource link --}}
                <div x-show="action === 'add_resource_link'" x-cloak class="space-y-3">
                    <div>
                        <x-input-label for="link_name" :value="__('Link label')" />
                        <x-text-input id="link_name" type="text" name="link_name" :value="old('link_name')"
                                      maxlength="200"
                                      x-bind:disabled="action !== 'add_resource_link'"
                                      placeholder="e.g. Manufacturer datasheet"
                                      class="mt-1 block w-full" />
                    </div>
                    <div>
                        <x-input-label for="link_url_add" :value="__('URL')" />
                        <x-text-input id="link_url_add" type="url" name="link_url" :value="old('link_url')"
                                      maxlength="1000"
                                      x-bind:disabled="action !== 'add_resource_link'"
                                      placeholder="https://…"
                                      class="mt-1 block w-full" />
                    </div>
                    <p class="text-xs text-gray-500">Appended to each selected type's resource links. Skipped for any type that already has this URL.</p>
                </div>

                {{-- Remove resource link --}}
                <div x-show="action === 'remove_resource_link'" x-cloak>
                    <x-input-label for="link_url_remove" :value="__('URL to remove')" />
                    @if ($existingUrls->isNotEmpty())
                        <select id="link_url_remove" name="link_url"
                                x-bind:disabled="action !== 'remove_resource_link'"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-brand-red focus:ring-brand-red">
                            <option value="">— pick a URL —</option>
                            @foreach ($existingUrls as $link)
                                <option value="{{ $link['url'] }}" @selected(old('link_url') === $link['url'])>
                                    {{ $link['name'] ?? $link['url'] }} — {{ $link['url'] }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">URLs found across the selected types. Removed from every selected type that has it.</p>
                    @else
                        <p class="mt-1 text-sm text-gray-500 italic">None of the selected types have any resource links yet.</p>
                    @endif
                </div>

                {{-- Interval months --}}
                <div x-show="action === 'set_interval_months'" x-cloak>
                    <x-input-label for="value_interval" :value="__('Inspection interval (months)')" />
                    <x-text-input id="value_interval" type="number" step="1" min="1" max="120"
                                  name="value" :value="old('value')"
                                  x-bind:disabled="action !== 'set_interval_months'"
                                  class="mt-1 block w-full sm:w-48" />
                    <p class="mt-1 text-xs text-gray-500">LOLER cadence applied to every selected type.</p>
                </div>

                {{-- Lifts people --}}
                <div x-show="action === 'set_lifts_people'" x-cloak>
                    <x-input-label :value="__('Lifts or supports people?')" />
                    <div class="mt-2 flex gap-4">
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" name="value" value="1"
                                   @checked(old('value') === '1')
                                   x-bind:disabled="action !== 'set_lifts_people'"
                                   class="text-brand-red focus:ring-brand-red">
                            <span class="text-sm">Yes</span>
                        </label>
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" name="value" value="0"
                                   @checked(old('value') === '0')
                                   x-bind:disabled="action !== 'set_lifts_people'"
                                   class="text-brand-red focus:ring-brand-red">
                            <span class="text-sm">No</span>
                        </label>
                    </div>
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-3 pt-4 border-t border-gray-200">
                    <a href="{{ route('kit-types.index') }}" class="text-sm text-gray-600 hover:text-gray-900 text-center">Cancel</a>
                    <x-primary-button>Preview Changes →</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
