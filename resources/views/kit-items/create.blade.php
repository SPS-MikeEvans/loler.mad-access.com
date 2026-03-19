<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Add Kit Item — {{ $client->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @php
                        $kitTypeData = $kitTypes->keyBy('id')->map(fn ($t) => [
                            'brand'           => $t->brand,
                            'swl_description' => $t->swl_description,
                            'lifts_people'    => $t->lifts_people,
                        ]);
                    @endphp

                    <form method="POST" action="{{ route('clients.kit-items.store', $client) }}" class="space-y-6"
                        x-data="{
                            types: {{ Js::from($kitTypeData) }},
                            manufacturer: {{ Js::from(old('manufacturer', '')) }},
                            swlKg: {{ Js::from(old('swl_kg', '')) }},
                            liftingPeople: {{ old('lifting_people', true) ? 'true' : 'false' }},
                            autoFilled: { manufacturer: false, swlKg: false },
                            selectType(id) {
                                const type = this.types[id];
                                if (!type) {
                                    this.autoFilled = { manufacturer: false, swlKg: false };
                                    return;
                                }
                                this.manufacturer = type.brand ?? '';
                                this.autoFilled.manufacturer = !!type.brand;
                                const match = (type.swl_description ?? '').match(/(\d+)\s*kg/i);
                                this.swlKg = match ? match[1] : '';
                                this.autoFilled.swlKg = !!match;
                                this.liftingPeople = type.lifts_people;
                            }
                        }">
                        @csrf

                        <div>
                            <x-input-label for="kit_type_id" :value="__('Equipment Type')" />
                            <select id="kit_type_id" name="kit_type_id" required
                                x-on:change="selectType($event.target.value)"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-brand-red focus:ring-brand-red">
                                <option value="">Select type…</option>
                                @foreach ($kitTypes->groupBy('category') as $category => $types)
                                    <optgroup label="{{ $category }}">
                                        @foreach ($types as $type)
                                            <option value="{{ $type->id }}" {{ old('kit_type_id') == $type->id ? 'selected' : '' }}>
                                                {{ $type->name }}{{ $type->brand ? ' (' . $type->brand . ')' : '' }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('kit_type_id')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="asset_tag" :value="__('Asset Tag / QR Code')" />
                                <x-text-input id="asset_tag" name="asset_tag" type="text" class="mt-1 block w-full"
                                    :value="old('asset_tag')" />
                                <x-input-error :messages="$errors->get('asset_tag')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="serial_no" :value="__('Serial Number')" />
                                <x-text-input id="serial_no" name="serial_no" type="text" class="mt-1 block w-full"
                                    :value="old('serial_no')" />
                                <x-input-error :messages="$errors->get('serial_no')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="manufacturer" :value="__('Manufacturer')" />
                                <x-text-input id="manufacturer" name="manufacturer" type="text" class="mt-1 block w-full"
                                    x-model="manufacturer" />
                                <p x-show="autoFilled.manufacturer" class="mt-1 text-xs text-brand-navy/60">Auto-filled from equipment type</p>
                                <x-input-error :messages="$errors->get('manufacturer')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="model" :value="__('Model')" />
                                <x-text-input id="model" name="model" type="text" class="mt-1 block w-full"
                                    :value="old('model')" />
                                <x-input-error :messages="$errors->get('model')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="purchase_date" :value="__('Purchase Date')" />
                                <x-text-input id="purchase_date" name="purchase_date" type="date" class="mt-1 block w-full"
                                    :value="old('purchase_date')" />
                                <x-input-error :messages="$errors->get('purchase_date')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="first_use_date" :value="__('First Use Date')" />
                                <x-text-input id="first_use_date" name="first_use_date" type="date" class="mt-1 block w-full"
                                    :value="old('first_use_date')" />
                                <p class="mt-1 text-xs text-gray-500">Used to calculate next inspection due date.</p>
                                <x-input-error :messages="$errors->get('first_use_date')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="swl_kg" :value="__('Safe Working Load (kg)')" />
                                <x-text-input id="swl_kg" name="swl_kg" type="number" class="mt-1 block w-full"
                                    x-model="swlKg" min="0" />
                                <p x-show="autoFilled.swlKg" class="mt-1 text-xs text-brand-navy/60">Auto-filled from equipment type</p>
                                <x-input-error :messages="$errors->get('swl_kg')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="status" :value="__('Status')" />
                                <select id="status" name="status"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-brand-red focus:ring-brand-red">
                                    <option value="in_service" {{ old('status', 'in_service') === 'in_service' ? 'selected' : '' }}>In Service</option>
                                    <option value="inspection_due" {{ old('status') === 'inspection_due' ? 'selected' : '' }}>Inspection Due</option>
                                    <option value="quarantined" {{ old('status') === 'quarantined' ? 'selected' : '' }}>Quarantined</option>
                                    <option value="retired" {{ old('status') === 'retired' ? 'selected' : '' }}>Retired</option>
                                </select>
                                <x-input-error :messages="$errors->get('status')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center gap-3">
                                <input type="checkbox" id="lifting_people" name="lifting_people" value="1"
                                    x-model="liftingPeople"
                                    class="h-4 w-4 text-brand-navy border-gray-300 rounded focus:ring-brand-red">
                                <x-input-label for="lifting_people" :value="__('Used for lifting people (LOLER people-lifting rules apply)')" />
                            </div>
                            <p x-show="liftingPeople" x-cloak class="mt-1 text-xs text-amber-600">
                                LOLER requires a maximum 6-month inspection interval for equipment that lifts people.
                            </p>
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>Save Kit Item</x-primary-button>
                            <a href="{{ route('clients.kit-items.index', $client) }}">
                                <x-secondary-button type="button">Cancel</x-secondary-button>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
