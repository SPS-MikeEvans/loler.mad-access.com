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

                        <div class="mb-4">
                            <x-input-label for="kit_type_id" :value="__('Equipment Type')" />
                            <select id="kit_type_id" name="kit_type_id"
                                    class="block mt-1 w-full border-gray-300 rounded-xl shadow-sm focus:border-brand-red focus:ring-brand-red">
                                <option value="">— Select type —</option>
                                @foreach ($kitTypes as $type)
                                    <option value="{{ $type->id }}" {{ old('kit_type_id') == $type->id ? 'selected' : '' }}>
                                        {{ $type->name }} ({{ $type->category }})
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('kit_type_id')" class="mt-2" />
                        </div>

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
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                            <div>
                                <x-input-label for="manufacturer" :value="__('Manufacturer')" />
                                <x-text-input id="manufacturer" class="block mt-1 w-full" type="text" name="manufacturer"
                                              :value="old('manufacturer')" />
                                <x-input-error :messages="$errors->get('manufacturer')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="model" :value="__('Model')" />
                                <x-text-input id="model" class="block mt-1 w-full" type="text" name="model"
                                              :value="old('model')" />
                                <x-input-error :messages="$errors->get('model')" class="mt-2" />
                            </div>
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
