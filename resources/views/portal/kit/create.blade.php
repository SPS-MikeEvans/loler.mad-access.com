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

                    <form method="POST" action="{{ route('portal.kit.store') }}"
                          @submit.prevent="validateAndSubmit($el)">
                        @csrf

                        {{-- Equipment type modal selector --}}
                        <div class="mb-4"
                             x-data="{
                                allTypes: {{ Js::from($kitTypes->map(fn($t) => ['id' => $t->id, 'name' => $t->name, 'category' => $t->category ?? '', 'brand' => $t->brand ?? '', 'interval' => $t->interval_months ?? 6, 'lifts' => $t->lifts_people])) }},
                                categories: {{ Js::from($categories) }},
                                brands: {{ Js::from($brands) }},
                                open: false,
                                search: '',
                                activeCategory: '',
                                activeBrand: '',
                                selectedId: {{ old('kit_type_id') ? (int) old('kit_type_id') : 'null' }},
                                selectedLabel: '{{ addslashes($kitTypes->firstWhere('id', old('kit_type_id'))?->name ?? '') }}',
                                selectedInterval: {{ old('kit_type_id') ? ($kitTypes->firstWhere('id', old('kit_type_id'))?->interval_months ?? 'null') : 'null' }},
                                selectedLifts: {{ old('kit_type_id') ? ($kitTypes->firstWhere('id', old('kit_type_id'))?->lifts_people ? 'true' : 'false') : 'null' }},
                                useCustom: {{ old('custom_type_name') ? 'true' : 'false' }},
                                customName: '{{ addslashes(old('custom_type_name', '')) }}',
                                get filtered() {
                                    return this.allTypes.filter(t =>
                                        (this.search === '' || t.name.toLowerCase().includes(this.search.toLowerCase()) || t.brand.toLowerCase().includes(this.search.toLowerCase()))
                                        && (this.activeCategory === '' || t.category === this.activeCategory)
                                        && (this.activeBrand === '' || t.brand === this.activeBrand)
                                    );
                                },
                                get displayLabel() {
                                    if (this.useCustom && this.customName) return this.customName + ' (custom — pending review)';
                                    return this.selectedLabel;
                                },
                                selectType(type) {
                                    this.selectedId = type.id;
                                    this.selectedLabel = type.name;
                                    this.selectedInterval = type.interval;
                                    this.selectedLifts = type.lifts;
                                    this.useCustom = false;
                                    this.customName = '';
                                    this.open = false;
                                },
                                confirmCustom() {
                                    if (!this.customName.trim()) return;
                                    this.selectedId = null;
                                    this.selectedLabel = '';
                                    this.selectedInterval = null;
                                    this.selectedLifts = null;
                                    this.useCustom = true;
                                    this.open = false;
                                },
                                openModal() {
                                    this.search = '';
                                    this.activeCategory = '';
                                    this.activeBrand = '';
                                    this.open = true;
                                },
                                validateAndSubmit(el) {
                                    if (!this.selectedId && (!this.useCustom || !this.customName.trim())) {
                                        this.openModal();
                                        return;
                                    }
                                    el.submit();
                                },
                             }">

                            {{-- Hidden fields always submitted --}}
                            <input type="hidden" name="kit_type_id" :value="selectedId">
                            <input type="hidden" name="custom_type_name" :value="useCustom ? customName : ''">

                            <x-input-label :value="__('Equipment Type')" />
                            <x-input-error :messages="$errors->get('kit_type_id')" class="mt-1" />
                            <x-input-error :messages="$errors->get('custom_type_name')" class="mt-1" />

                            {{-- Summary card when something is selected --}}
                            <template x-if="displayLabel !== ''">
                                <div class="mt-1 space-y-1">
                                    <div class="flex items-start justify-between gap-3 rounded-xl border border-gray-300 bg-gray-50 px-4 py-3">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900" x-text="displayLabel"></p>
                                            <p x-show="selectedInterval !== null"
                                               class="text-xs text-gray-500 mt-0.5"
                                               x-text="'Inspection every ' + selectedInterval + ' months' + (selectedLifts ? ' · Lifts people' : '')"></p>
                                        </div>
                                        <button type="button" @click="openModal()"
                                                class="shrink-0 text-xs text-brand-navy underline hover:text-brand-red mt-0.5">
                                            Change
                                        </button>
                                    </div>
                                </div>
                            </template>

                            {{-- Trigger button when nothing selected --}}
                            <template x-if="displayLabel === ''">
                                <button type="button" @click="openModal()"
                                        class="mt-1 w-full flex items-center justify-between gap-2 rounded-xl border border-gray-300 bg-white px-4 py-3 text-left text-sm text-gray-400 shadow-sm hover:border-brand-red focus:outline-none focus:ring-2 focus:ring-brand-red transition">
                                    <span>Choose equipment type…</span>
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </button>
                            </template>

                            {{-- Modal --}}
                            <div x-show="open" x-cloak
                                 @keydown.escape.window="open = false"
                                 class="fixed inset-0 z-50 flex items-end sm:items-center justify-center">

                                {{-- Backdrop --}}
                                <div class="absolute inset-0 bg-gray-900/60"
                                     @click="open = false"
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0"
                                     x-transition:enter-end="opacity-100"
                                     x-transition:leave="transition ease-in duration-150"
                                     x-transition:leave-start="opacity-100"
                                     x-transition:leave-end="opacity-0"></div>

                                {{-- Panel --}}
                                <div class="relative w-full sm:max-w-2xl bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl flex flex-col max-h-[90vh]"
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                     x-transition:leave="transition ease-in duration-150"
                                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                     @click.stop>

                                    {{-- Header --}}
                                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 shrink-0">
                                        <h2 class="text-base font-semibold text-gray-900">Choose Equipment Type</h2>
                                        <button type="button" @click="open = false"
                                                class="rounded-full p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>

                                    {{-- Search + filters --}}
                                    <div class="px-5 pt-4 pb-3 shrink-0 space-y-3 border-b border-gray-100">
                                        <input x-model="search"
                                               type="search"
                                               placeholder="Search by name or brand…"
                                               x-init="$nextTick(() => { if (open) $el.focus() })"
                                               x-effect="if (open) $nextTick(() => $el.focus())"
                                               class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-brand-red focus:ring-brand-red">

                                        {{-- Category pills --}}
                                        <div class="flex gap-2 overflow-x-auto pb-1" style="-ms-overflow-style:none; scrollbar-width:none;">
                                            <button type="button" @click="activeCategory = ''"
                                                    :class="activeCategory === '' ? 'bg-brand-navy text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                                                    class="shrink-0 px-3 py-1 rounded-full text-xs font-medium transition">All</button>
                                            @foreach ($categories as $cat)
                                                <button type="button" @click="activeCategory = {{ Js::from($cat) }}"
                                                        :class="activeCategory === {{ Js::from($cat) }} ? 'bg-brand-navy text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                                                        class="shrink-0 px-3 py-1 rounded-full text-xs font-medium transition">{{ $cat }}</button>
                                            @endforeach
                                        </div>

                                        {{-- Brand pills --}}
                                        <div class="flex gap-2 overflow-x-auto pb-1" style="-ms-overflow-style:none; scrollbar-width:none;">
                                            <button type="button" @click="activeBrand = ''"
                                                    :class="activeBrand === '' ? 'bg-brand-red text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                                                    class="shrink-0 px-3 py-1 rounded-full text-xs font-medium transition">All Brands</button>
                                            @foreach ($brands as $brand)
                                                <button type="button" @click="activeBrand = {{ Js::from($brand) }}"
                                                        :class="activeBrand === {{ Js::from($brand) }} ? 'bg-brand-red text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                                                        class="shrink-0 px-3 py-1 rounded-full text-xs font-medium transition">{{ $brand }}</button>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- Results (scrollable) --}}
                                    <div class="overflow-y-auto flex-1 px-5 py-4">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                            <template x-for="type in filtered" :key="type.id">
                                                <button type="button" @click="selectType(type)"
                                                        class="text-left p-3 rounded-xl border border-gray-200 hover:border-brand-red hover:bg-red-50 transition group">
                                                    <p class="text-sm font-semibold text-gray-900 group-hover:text-brand-red" x-text="type.name"></p>
                                                    <div class="mt-1 flex flex-wrap items-center gap-x-1.5 gap-y-1">
                                                        <span x-show="type.brand" x-text="type.brand" class="text-xs text-gray-500"></span>
                                                        <span x-show="type.brand && type.category" class="text-xs text-gray-300">·</span>
                                                        <span x-show="type.category" x-text="type.category"
                                                              class="text-xs px-1.5 py-0.5 rounded bg-gray-100 text-gray-500"></span>
                                                    </div>
                                                </button>
                                            </template>
                                        </div>

                                        <p x-show="filtered.length === 0"
                                           class="py-8 text-center text-sm text-gray-400 italic">
                                            No equipment matches your search.
                                            <span class="block mt-1 text-gray-400">Try a different keyword, or use the custom entry below.</span>
                                        </p>

                                        {{-- Custom entry fallback --}}
                                        <div class="mt-5 border-t border-gray-100 pt-5">
                                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Can't find your equipment?</p>
                                            <div class="flex gap-2">
                                                <input x-model="customName"
                                                       type="text"
                                                       placeholder="e.g. Singing Rock Roof Master Harness"
                                                       maxlength="100"
                                                       class="flex-1 rounded-xl border-gray-300 text-sm shadow-sm focus:border-brand-red focus:ring-brand-red">
                                                <button type="button" @click="confirmCustom()"
                                                        :disabled="!customName.trim()"
                                                        class="shrink-0 px-4 py-2 rounded-xl bg-brand-navy text-white text-sm font-medium hover:bg-brand-red transition disabled:opacity-40 disabled:cursor-not-allowed">
                                                    Use This
                                                </button>
                                            </div>
                                            <p class="mt-2 text-xs text-gray-400">Our team will confirm the correct type when they review your item.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
