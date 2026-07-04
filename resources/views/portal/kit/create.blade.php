<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Add Equipment
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-2xl mx-auto mobile-shell">
            <div class="mobile-card overflow-hidden sm:rounded-lg">
                <div class="mobile-card-body">

                    <p class="text-sm text-gray-600 mb-6">
                        Submit the details below and our team will review and activate your equipment before its first inspection. You only need to fill in what you know — we can complete missing details when we collect the item.
                    </p>

                    <form method="POST" action="{{ route('portal.kit.store') }}">
                        @csrf

                        {{-- Equipment type modal selector --}}
                        <x-equipment-type-picker class="mb-4"
                            :kit-types="$kitTypes"
                            :selected-id="old('kit_type_id')"
                            :custom-name="old('custom_type_name')"
                            custom-suffix=" (custom — pending review)"
                            custom-hint="Our team will confirm the correct type when they review your item." />

                        <div class="mb-4">
                            <x-input-label for="asset_tag" :value="__('Asset Tag (if labelled)')" />
                            <x-text-input id="asset_tag" class="block mt-1 w-full" type="text" name="asset_tag"
                                          :value="old('asset_tag')" placeholder="e.g. H-001" />
                            <x-input-error :messages="$errors->get('asset_tag')" class="mt-2" />
                        </div>

                        <div class="mb-4">
                            <x-input-label for="serial_no" :value="__('Serial Number')" />
                            <x-text-input id="serial_no" class="block mt-1 w-full" type="text" name="serial_no"
                                          :value="old('serial_no')" placeholder="Manufacturer's serial number" />
                            <x-input-error :messages="$errors->get('serial_no')" class="mt-2" />
                            <p class="mt-1 text-xs text-gray-500">For block creation, serial numbers are left blank and can be edited later.</p>
                        </div>

                        <div class="mb-4 rounded-xl border border-gray-200 p-4">
                            <h3 class="text-sm font-semibold text-gray-900">Block creation</h3>
                            <p class="mt-1 text-xs text-gray-500">
                                Use this for sets or lots. Quantity 1 keeps the normal single-item behavior.
                            </p>

                            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-4">
                                <div>
                                    <x-input-label for="quantity" :value="__('Quantity')" />
                                    <x-text-input id="quantity" name="quantity" type="number" min="1" max="100"
                                                  class="block mt-1 w-full" :value="old('quantity', 1)" />
                                    <x-input-error :messages="$errors->get('quantity')" class="mt-2" />
                                </div>

                                <div class="sm:col-span-3">
                                    <x-input-label for="asset_tag_prefix" :value="__('Asset Tag Prefix (optional)')" />
                                    <x-text-input id="asset_tag_prefix" name="asset_tag_prefix" type="text"
                                                  class="block mt-1 w-full" :value="old('asset_tag_prefix')" placeholder="e.g. CAR-" />
                                    <x-input-error :messages="$errors->get('asset_tag_prefix')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="asset_tag_start" :value="__('Start')" />
                                    <x-text-input id="asset_tag_start" name="asset_tag_start" type="number" min="0"
                                                  class="block mt-1 w-full" :value="old('asset_tag_start', 1)" />
                                    <x-input-error :messages="$errors->get('asset_tag_start')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="asset_tag_padding" :value="__('Padding')" />
                                    <x-text-input id="asset_tag_padding" name="asset_tag_padding" type="number" min="1" max="6"
                                                  class="block mt-1 w-full" :value="old('asset_tag_padding', 3)" />
                                    <x-input-error :messages="$errors->get('asset_tag_padding')" class="mt-2" />
                                </div>
                            </div>

                            <p class="mt-3 text-xs text-gray-500">
                                With prefix CAR-, start 1 and padding 3, a quantity of 10 creates CAR-001 to CAR-010. Leave the prefix blank for blank asset tags.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                            <div x-data="{ value: '{{ addslashes(old('manufacturer', '')) }}' }"
                                 @equipment-custom-confirmed.window="if (!value.trim()) value = $event.detail.manufacturer">
                                <x-input-label for="manufacturer" :value="__('Manufacturer')" />
                                <input id="manufacturer" type="text" name="manufacturer"
                                       x-model="value"
                                       class="border-gray-300 focus:border-brand-red focus:ring-brand-red rounded-md shadow-sm block mt-1 w-full" />
                                <x-input-error :messages="$errors->get('manufacturer')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="model" :value="__('Model')" />
                                <x-text-input id="model" class="block mt-1 w-full" type="text" name="model"
                                              :value="old('model')" />
                                <x-input-error :messages="$errors->get('model')" class="mt-2" />
                            </div>
                        </div>

                        <div class="mb-4" x-data="{
                            mode: '{{ old('new_group_name') ? 'create' : 'select' }}',
                            groupId: '{{ old('kit_group_id', '') }}',
                            newName: '{{ addslashes(old('new_group_name', '')) }}',
                        }">
                            <x-input-label :value="__('Kit Group (optional)')" />

                            <template x-if="mode === 'select'">
                                <div class="mt-1 flex gap-2">
                                    <select x-model="groupId" name="kit_group_id"
                                            class="flex-1 rounded-xl border-gray-300 text-sm shadow-sm focus:border-brand-red focus:ring-brand-red">
                                        <option value="">— Not in a group —</option>
                                        @foreach ($kitGroups as $group)
                                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" @click="mode = 'create'; groupId = ''"
                                            class="shrink-0 px-3 py-2 rounded-xl bg-brand-navy text-white text-xs font-medium hover:bg-brand-red transition">
                                        + New
                                    </button>
                                </div>
                            </template>

                            <template x-if="mode === 'create'">
                                <div class="mt-1 flex gap-2">
                                    <input x-model="newName" name="new_group_name" type="text" maxlength="100"
                                           placeholder="New group name (e.g. Personal Set, Carabiners Bag A)"
                                           class="flex-1 rounded-xl border-gray-300 text-sm shadow-sm focus:border-brand-red focus:ring-brand-red">
                                    <button type="button" @click="mode = 'select'; newName = ''"
                                            class="shrink-0 px-3 py-2 rounded-xl bg-gray-100 text-gray-700 text-xs font-medium hover:bg-gray-200 transition">
                                        Cancel
                                    </button>
                                </div>
                            </template>

                            {{-- Hidden kit_group_id when in create mode so the field still posts (empty) --}}
                            <template x-if="mode === 'create'">
                                <input type="hidden" name="kit_group_id" value="">
                            </template>

                            <x-input-error :messages="$errors->get('kit_group_id')" class="mt-2" />
                            <x-input-error :messages="$errors->get('new_group_name')" class="mt-2" />
                            <p class="mt-1 text-xs text-gray-500">Use groups to organise equipment (e.g. a personal kit set, or a bag of carabiners).</p>
                        </div>

                        <div class="mb-6">
                            <label class="flex items-center gap-3">
                                <input type="checkbox" name="lifting_people" value="1"
                                       {{ old('lifting_people') ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-brand-red shadow-sm focus:ring-brand-red">
                                <span class="text-sm text-gray-700">This item is used to lift or support people</span>
                            </label>
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>Submit Equipment</x-primary-button>
                            <a href="{{ route('portal.kit.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
