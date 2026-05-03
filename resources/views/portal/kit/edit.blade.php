<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Edit Equipment — {{ $kitItem->typeName() }}
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-3xl mx-auto mobile-shell">
            <div class="mobile-card sm:rounded-lg">
                <div class="mobile-card-body">

                    @if ($locked)
                        <div class="mb-6 px-4 py-3 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800">
                            <p class="font-semibold">Some fields are locked</p>
                            <p class="mt-1">This item has inspection records on file, so identity fields (manufacturer, model, serial number, type, dates) cannot be changed. Please contact us if you spot an error.</p>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('portal.kit.update', $kitItem) }}" class="space-y-5">
                        @csrf
                        @method('PATCH')

                        <div>
                            <x-input-label for="kit_type_id" :value="__('Equipment Type')" />
                            <select id="kit_type_id" name="kit_type_id"
                                @disabled($locked)
                                class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm text-sm focus:border-brand-red focus:ring-brand-red @if ($locked) bg-gray-100 @endif">
                                <option value="">— Custom / Not listed —</option>
                                @foreach ($kitTypes as $type)
                                    <option value="{{ $type->id }}" @selected(old('kit_type_id', $kitItem->kit_type_id) == $type->id)>
                                        {{ $type->name }}@if ($type->category) ({{ $type->category }})@endif
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('kit_type_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="custom_type_name" :value="__('Custom Type Name')" />
                            <x-text-input id="custom_type_name" name="custom_type_name" type="text"
                                class="mt-1 block w-full" :value="old('custom_type_name', $kitItem->custom_type_name)" maxlength="100" />
                            <x-input-error :messages="$errors->get('custom_type_name')" class="mt-2" />
                            <p class="mt-1 text-xs text-gray-500">Used when no listed equipment type matches.</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="manufacturer" :value="__('Manufacturer')" />
                                <x-text-input id="manufacturer" name="manufacturer" type="text"
                                    class="mt-1 block w-full @if ($locked) bg-gray-100 @endif"
                                    :value="old('manufacturer', $kitItem->manufacturer)" maxlength="100"
                                    :disabled="$locked" />
                                <x-input-error :messages="$errors->get('manufacturer')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="model" :value="__('Model')" />
                                <x-text-input id="model" name="model" type="text"
                                    class="mt-1 block w-full @if ($locked) bg-gray-100 @endif"
                                    :value="old('model', $kitItem->model)" maxlength="100"
                                    :disabled="$locked" />
                                <x-input-error :messages="$errors->get('model')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="serial_no" :value="__('Serial Number')" />
                                <x-text-input id="serial_no" name="serial_no" type="text"
                                    class="mt-1 block w-full @if ($locked) bg-gray-100 @endif"
                                    :value="old('serial_no', $kitItem->serial_no)" maxlength="100"
                                    :disabled="$locked" />
                                <x-input-error :messages="$errors->get('serial_no')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="asset_tag" :value="__('Asset Tag')" />
                                <x-text-input id="asset_tag" name="asset_tag" type="text"
                                    class="mt-1 block w-full" :value="old('asset_tag', $kitItem->asset_tag)" maxlength="100" />
                                <x-input-error :messages="$errors->get('asset_tag')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="purchase_date" :value="__('Purchase Date')" />
                                <x-text-input id="purchase_date" name="purchase_date" type="date"
                                    class="mt-1 block w-full @if ($locked) bg-gray-100 @endif"
                                    :value="old('purchase_date', $kitItem->purchase_date?->format('Y-m-d'))"
                                    :disabled="$locked" />
                                <x-input-error :messages="$errors->get('purchase_date')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="first_use_date" :value="__('First Use Date')" />
                                <x-text-input id="first_use_date" name="first_use_date" type="date"
                                    class="mt-1 block w-full @if ($locked) bg-gray-100 @endif"
                                    :value="old('first_use_date', $kitItem->first_use_date?->format('Y-m-d'))"
                                    :disabled="$locked" />
                                <x-input-error :messages="$errors->get('first_use_date')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="swl_kg" :value="__('Safe Working Load (kg)')" />
                                <x-text-input id="swl_kg" name="swl_kg" type="number" min="0"
                                    class="mt-1 block w-full" :value="old('swl_kg', $kitItem->swl_kg)" />
                                <x-input-error :messages="$errors->get('swl_kg')" class="mt-2" />
                            </div>

                            <div class="flex items-center pt-6 sm:pt-7">
                                <label class="inline-flex items-center gap-2">
                                    <input type="hidden" name="lifting_people" value="0" />
                                    <input type="checkbox" name="lifting_people" value="1"
                                        @checked(old('lifting_people', $kitItem->lifting_people))
                                        class="rounded border-gray-300 text-brand-red focus:ring-brand-red" />
                                    <span class="text-sm text-gray-700">Used for lifting people</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <x-input-label for="kit_group_id" :value="__('Kit Group')" />
                            <select id="kit_group_id" name="kit_group_id"
                                class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm text-sm focus:border-brand-red focus:ring-brand-red">
                                <option value="">— Not in a group —</option>
                                @foreach ($kitGroups as $group)
                                    <option value="{{ $group->id }}" @selected(old('kit_group_id', $kitItem->kit_group_id) == $group->id)>
                                        {{ $group->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('kit_group_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="flag_notes" :value="__('Inspection Notes (optional)')" />
                            <textarea id="flag_notes" name="flag_notes" rows="3" maxlength="1000"
                                class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm text-sm focus:border-brand-red focus:ring-brand-red">{{ old('flag_notes', $kitItem->flag_notes) }}</textarea>
                            <x-input-error :messages="$errors->get('flag_notes')" class="mt-2" />
                        </div>

                        <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-3">
                            <a href="{{ route('portal.kit.show', $kitItem) }}" class="text-sm text-brand-navy hover:text-brand-red text-center">Cancel</a>
                            <x-primary-button>Save Changes</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
