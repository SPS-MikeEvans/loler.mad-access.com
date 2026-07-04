@props([
    'kitTypes',
    'selectedId' => null,
    'customName' => null,
    'customSuffix' => ' (custom)',
    'customHint' => null,
])

@php
    $typesPayload = $kitTypes->map(fn ($t) => [
        'id' => $t->id,
        'name' => $t->name,
        'category' => $t->category ?? '',
        'brand' => $t->brand ?? '',
        'interval' => $t->interval_months ?? 6,
        'lifts' => (bool) $t->lifts_people,
        'swl_description' => $t->swl_description ?? '',
    ])->values();
    $categories = $kitTypes->pluck('category')->filter()->unique()->sort()->values();
    $brands = $kitTypes->pluck('brand')->filter()->unique()->sort()->values();
    $initialType = $selectedId ? $kitTypes->firstWhere('id', (int) $selectedId) : null;
@endphp

<div {{ $attributes }}
     x-data="{
        allTypes: {{ Js::from($typesPayload) }},
        open: false,
        search: '',
        activeCategory: '',
        activeBrand: '',
        selectedId: {{ $initialType ? (int) $selectedId : 'null' }},
        selectedLabel: {{ Js::from($initialType?->name ?? '') }},
        selectedInterval: {{ $initialType ? ($initialType->interval_months ?? 6) : 'null' }},
        selectedLifts: {{ $initialType ? ($initialType->lifts_people ? 'true' : 'false') : 'null' }},
        useCustom: {{ $customName ? 'true' : 'false' }},
        customName: {{ Js::from($customName ?? '') }},
        customSuffix: {{ Js::from($customSuffix) }},
        get filtered() {
            return this.allTypes.filter(t =>
                (this.search === '' || t.name.toLowerCase().includes(this.search.toLowerCase()) || t.brand.toLowerCase().includes(this.search.toLowerCase()))
                && (this.activeCategory === '' || t.category === this.activeCategory)
                && (this.activeBrand === '' || t.brand === this.activeBrand)
            );
        },
        get hasNoResults() {
            return this.filtered.length === 0 && this.search.trim() !== '';
        },
        get displayLabel() {
            if (this.useCustom && this.customName) return this.customName + this.customSuffix;
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
            window.dispatchEvent(new CustomEvent('equipment-type-selected', { detail: type }));
        },
        confirmCustom() {
            if (!this.customName.trim()) return;
            this.selectedId = null;
            this.selectedLabel = '';
            this.selectedInterval = null;
            this.selectedLifts = null;
            this.useCustom = true;
            this.open = false;
            window.dispatchEvent(new CustomEvent('equipment-type-cleared'));
            if (this.activeBrand) {
                window.dispatchEvent(new CustomEvent('equipment-custom-confirmed', { detail: { manufacturer: this.activeBrand } }));
            }
        },
        useSearchAsCustom() {
            if (!this.search.trim()) return;
            this.customName = this.search.trim();
            this.confirmCustom();
        },
        openModal() {
            this.search = '';
            this.activeCategory = '';
            this.activeBrand = '';
            this.open = true;
        },
     }"
     x-init="const form = $el.closest('form');
        if (form) {
            form.addEventListener('submit', (e) => {
                if (!selectedId && !(useCustom && customName.trim())) {
                    e.preventDefault();
                    openModal();
                }
            });
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
                <div x-data="{ expanded: false }">
                    <div :class="expanded ? '' : 'max-h-8 overflow-hidden'"
                         class="flex flex-wrap gap-2 transition-all">
                        <button type="button" @click="activeCategory = ''"
                                :class="activeCategory === '' ? 'bg-brand-navy text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                                class="px-3 py-1 rounded-full text-xs font-medium transition">All</button>
                        @foreach ($categories as $cat)
                            <button type="button" @click="activeCategory = {{ Js::from($cat) }}"
                                    :class="activeCategory === {{ Js::from($cat) }} ? 'bg-brand-navy text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                                    class="px-3 py-1 rounded-full text-xs font-medium transition">{{ $cat }}</button>
                        @endforeach
                    </div>
                    @if ($categories->count() > 5)
                        <button type="button" @click="expanded = !expanded"
                                class="mt-1 text-xs text-brand-navy hover:text-brand-red font-medium"
                                x-text="expanded ? 'Show fewer categories' : 'Show all categories'"></button>
                    @endif
                </div>

                {{-- Brand pills --}}
                <div x-data="{ expanded: false }">
                    <div :class="expanded ? '' : 'max-h-8 overflow-hidden'"
                         class="flex flex-wrap gap-2 transition-all">
                        <button type="button" @click="activeBrand = ''"
                                :class="activeBrand === '' ? 'bg-brand-red text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                                class="px-3 py-1 rounded-full text-xs font-medium transition">All Brands</button>
                        @foreach ($brands as $brand)
                            <button type="button" @click="activeBrand = {{ Js::from($brand) }}"
                                    :class="activeBrand === {{ Js::from($brand) }} ? 'bg-brand-red text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                                    class="px-3 py-1 rounded-full text-xs font-medium transition">{{ $brand }}</button>
                        @endforeach
                    </div>
                    @if ($brands->count() > 5)
                        <button type="button" @click="expanded = !expanded"
                                class="mt-1 text-xs text-brand-navy hover:text-brand-red font-medium"
                                x-text="expanded ? 'Show fewer brands' : 'Show all brands'"></button>
                    @endif
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

                {{-- Prominent zero-results CTA: one-tap promote search → custom entry --}}
                <div x-show="hasNoResults"
                     class="mt-2 rounded-xl border border-dashed border-brand-red/40 bg-red-50/40 p-4 text-center">
                    <p class="text-sm text-gray-700">
                        No equipment matches "<span class="font-semibold" x-text="search"></span>".
                    </p>
                    <button type="button" @click="useSearchAsCustom()"
                            class="mt-3 inline-flex items-center gap-2 rounded-xl bg-brand-navy px-4 py-2 text-sm font-medium text-white hover:bg-brand-red transition">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Add "<span x-text="search"></span>" as a custom type</span>
                    </button>
                    @if ($customHint)
                        <p class="mt-2 text-xs text-gray-500">{{ $customHint }}</p>
                    @endif
                </div>

                <p x-show="!hasNoResults && filtered.length === 0"
                   class="py-8 text-center text-sm text-gray-400 italic">
                    Start typing to search, or use the custom entry below.
                </p>

                {{-- Custom entry fallback (still available for users who want to type something different from their search) --}}
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
                    @if ($customHint)
                        <p class="mt-2 text-xs text-gray-400">{{ $customHint }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
