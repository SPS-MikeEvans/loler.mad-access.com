<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Edit Kit Type — {{ $kitType->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">

                    @if ($errors->any())
                        <div class="mb-6 px-4 py-3 bg-red-100 text-red-800 rounded-lg">
                            <strong>Please correct the errors below.</strong>
                            <ul class="mt-1 list-disc list-inside text-sm">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('kit-types.update', $kitType) }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div class="sm:col-span-2">
                                <x-input-label for="name" :value="__('Name')" />
                                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                    :value="old('name', $kitType->name)" required autofocus />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="category" :value="__('Category')" />
                                <x-text-input id="category" name="category" type="text" class="mt-1 block w-full"
                                    :value="old('category', $kitType->category)" />
                                <p class="mt-1 text-xs text-gray-500">e.g. Harness, Descender, Connector</p>
                                <x-input-error :messages="$errors->get('category')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="brand" :value="__('Brand / Manufacturer')" />
                                <x-text-input id="brand" name="brand" type="text" class="mt-1 block w-full"
                                    :value="old('brand', $kitType->brand)" />
                                <x-input-error :messages="$errors->get('brand')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="interval_months" :value="__('Inspection Interval (months)')" />
                                <x-text-input id="interval_months" name="interval_months" type="number"
                                    class="mt-1 block w-full" :value="old('interval_months', $kitType->interval_months)"
                                    min="1" max="120" required />
                                <x-input-error :messages="$errors->get('interval_months')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="swl_description" :value="__('SWL / Rated Strength')" />
                                <x-text-input id="swl_description" name="swl_description" type="text" class="mt-1 block w-full"
                                    :value="old('swl_description', $kitType->swl_description)" />
                                <p class="mt-1 text-xs text-gray-500">e.g. Max user weight: 140 kg, or MBS: 27 kN</p>
                                <x-input-error :messages="$errors->get('swl_description')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="inspection_price" :value="__('Inspection Price (£)')" />
                                <x-text-input id="inspection_price" name="inspection_price" type="number" step="0.01" min="0" class="mt-1 block w-full"
                                    :value="old('inspection_price', $kitType->inspection_price)" />
                                <p class="mt-1 text-xs text-gray-500">Cost charged per inspection of this kit type</p>
                                <x-input-error :messages="$errors->get('inspection_price')" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <input type="checkbox" id="lifts_people" name="lifts_people" value="1"
                                {{ old('lifts_people', $kitType->lifts_people) ? 'checked' : '' }}
                                class="h-4 w-4 text-brand-navy border-gray-300 rounded focus:ring-brand-red">
                            <x-input-label for="lifts_people" :value="__('Used for lifting people (LOLER people-lifting rules apply)')" />
                        </div>

                        {{-- PDF Documents --}}
                        <div x-data="{ open: {{ ($kitType->spec_pdf_path || $kitType->inspection_pdf_path || $errors->hasAny(['spec_pdf_path', 'inspection_pdf_path'])) ? 'true' : 'false' }} }"
                             class="border border-gray-200 rounded-lg">
                            <button type="button" x-on:click="open = !open"
                                class="w-full flex items-center justify-between px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg">
                                <span>PDF Documents</span>
                                <svg x-bind:class="open ? 'rotate-180' : ''" class="h-4 w-4 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="open" class="px-4 pb-4 space-y-5">

                                {{-- Spec PDF --}}
                                <div x-data="{ removing: false }">
                                    <x-input-label for="spec_pdf_path" :value="__('Product Specification PDF')" />
                                    <p class="mt-0.5 text-xs text-gray-500">Manufacturer\'s product specifications document.</p>
                                    @if ($kitType->spec_pdf_path)
                                        <div class="mt-2 flex items-center gap-3" x-show="!removing">
                                            <a href="{{ Storage::url($kitType->spec_pdf_path) }}" target="_blank" rel="noopener"
                                               class="inline-flex items-center gap-1.5 text-sm text-brand-navy hover:text-brand-red">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h4a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" /></svg>
                                                View current PDF
                                            </a>
                                            <span class="text-gray-300">|</span>
                                            <button type="button" x-on:click="removing = true"
                                                class="text-sm text-red-500 hover:text-red-700">Remove</button>
                                        </div>
                                        <div x-show="removing" class="mt-2 flex items-center gap-3">
                                            <span class="text-sm text-red-600">PDF will be removed on save.</span>
                                            <button type="button" x-on:click="removing = false" class="text-sm text-gray-500 hover:text-gray-700">Undo</button>
                                            <input type="hidden" name="remove_spec_pdf" x-bind:value="removing ? '1' : ''">
                                        </div>
                                    @endif
                                    <div class="mt-2" x-show="!removing">
                                        <input type="file" id="spec_pdf_path" name="spec_pdf_path" accept=".pdf"
                                            class="block text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:bg-brand-navy/10 file:text-brand-navy hover:file:bg-brand-navy/20">
                                        @if ($kitType->spec_pdf_path)
                                            <p class="mt-0.5 text-xs text-gray-400">Uploading a new file will replace the current one.</p>
                                        @endif
                                    </div>
                                    <x-input-error :messages="$errors->get('spec_pdf_path')" class="mt-2" />
                                </div>

                                {{-- Inspection PDF --}}
                                <div x-data="{ removing: false }">
                                    <x-input-label for="inspection_pdf_path" :value="__('Inspection Procedure PDF')" />
                                    <p class="mt-0.5 text-xs text-gray-500">Manufacturer\'s inspection procedure document.</p>
                                    @if ($kitType->inspection_pdf_path)
                                        <div class="mt-2 flex items-center gap-3" x-show="!removing">
                                            <a href="{{ Storage::url($kitType->inspection_pdf_path) }}" target="_blank" rel="noopener"
                                               class="inline-flex items-center gap-1.5 text-sm text-brand-navy hover:text-brand-red">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h4a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" /></svg>
                                                View current PDF
                                            </a>
                                            <span class="text-gray-300">|</span>
                                            <button type="button" x-on:click="removing = true"
                                                class="text-sm text-red-500 hover:text-red-700">Remove</button>
                                        </div>
                                        <div x-show="removing" class="mt-2 flex items-center gap-3">
                                            <span class="text-sm text-red-600">PDF will be removed on save.</span>
                                            <button type="button" x-on:click="removing = false" class="text-sm text-gray-500 hover:text-gray-700">Undo</button>
                                            <input type="hidden" name="remove_inspection_pdf" x-bind:value="removing ? '1' : ''">
                                        </div>
                                    @endif
                                    <div class="mt-2" x-show="!removing">
                                        <input type="file" id="inspection_pdf_path" name="inspection_pdf_path" accept=".pdf"
                                            class="block text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:bg-brand-navy/10 file:text-brand-navy hover:file:bg-brand-navy/20">
                                        @if ($kitType->inspection_pdf_path)
                                            <p class="mt-0.5 text-xs text-gray-400">Uploading a new file will replace the current one.</p>
                                        @endif
                                    </div>
                                    <x-input-error :messages="$errors->get('inspection_pdf_path')" class="mt-2" />
                                </div>

                            </div>
                        </div>

                        {{-- Checklist & Instructions --}}
                        @php
                            $linksData = old('resources_links', $kitType->resources_links ?? []);
                            if (empty($linksData)) {
                                $linksData = [['name' => '', 'url' => '']];
                            }
                            $checklistData = old('checklist_json', $kitType->checklist_json ?? []);
                            if (empty($checklistData)) {
                                $checklistData = [['category' => 'Visual', 'text' => '']];
                            }
                        @endphp
                        <div x-data="{ open: {{ ($kitType->checklist_json || $kitType->instructions || $kitType->resources_links || $errors->hasAny(['resources_links', 'checklist_json', 'instructions'])) ? 'true' : 'false' }} }"
                             class="border border-gray-200 rounded-lg">
                            <button type="button" x-on:click="open = !open"
                                class="w-full flex items-center justify-between px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg">
                                <span>Checklist &amp; Instructions</span>
                                <svg x-bind:class="open ? 'rotate-180' : ''" class="h-4 w-4 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="open" class="px-4 pb-5 space-y-5">

                                <div>
                                    <x-input-label for="instructions" :value="__('Inspection Instructions')" />
                                    <textarea id="instructions" name="instructions" rows="4"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-brand-red focus:ring-brand-red text-sm"
                                        placeholder="Guidance shown to the inspector before the checklist…">{{ old('instructions', $kitType->instructions) }}</textarea>
                                    <x-input-error :messages="$errors->get('instructions')" class="mt-2" />
                                </div>

                                {{-- Resource Links --}}
                                <div x-data="{ links: @js($linksData) }">
                                    <x-input-label :value="__('Resource Links')" />
                                    <p class="mt-0.5 mb-3 text-xs text-gray-500">Links shown to the inspector (e.g. manufacturer guides, standards documents).</p>
                                    <div class="space-y-2">
                                        <template x-for="(link, i) in links" :key="i">
                                            <div class="flex gap-2 items-center">
                                                <input :name="`resources_links[${i}][name]`" x-model="link.name" type="text"
                                                    placeholder="Link name (e.g. Petzl User Guide)"
                                                    class="flex-1 border-gray-300 rounded-md shadow-sm text-sm focus:border-brand-red focus:ring-brand-red">
                                                <input :name="`resources_links[${i}][url]`" x-model="link.url" type="text"
                                                    placeholder="https://..."
                                                    class="flex-[2] border-gray-300 rounded-md shadow-sm text-sm focus:border-brand-red focus:ring-brand-red">
                                                <button type="button" x-on:click="links.splice(i, 1)"
                                                    class="shrink-0 w-7 h-7 flex items-center justify-center rounded-full text-red-400 hover:bg-red-50 hover:text-red-600 text-xl leading-none" title="Remove link">×</button>
                                            </div>
                                        </template>
                                    </div>
                                    <button type="button" x-on:click="links.push({ name: '', url: '' })"
                                        class="mt-2 text-sm text-brand-navy hover:text-brand-red font-medium">+ Add link</button>
                                </div>

                                {{-- Checklist --}}
                                <datalist id="checklist-categories">
                                    <option>Documentation</option>
                                    <option>Fittings &amp; Attachments</option>
                                    <option>Functional</option>
                                    <option>General Condition</option>
                                    <option>Identification</option>
                                    <option>Load-bearing Parts</option>
                                    <option>Marking</option>
                                    <option>Moving Parts</option>
                                    <option>Operational</option>
                                    <option>Overall</option>
                                    <option>Tactile</option>
                                    <option>Visual</option>
                                </datalist>
                                <div x-data="{ checks: @js($checklistData), defaultChecklist: @js($defaultChecklist) }">
                                    <div class="flex items-center justify-between mb-1">
                                        <x-input-label :value="__('Checklist Template')" />
                                        <button type="button"
                                            x-on:click="checks.some(c => c.text.trim())
                                                ? (confirm('Replace the current checklist with the generic default? Your changes will be lost.') && (checks = JSON.parse(JSON.stringify(defaultChecklist))))
                                                : (checks = JSON.parse(JSON.stringify(defaultChecklist)))"
                                            class="text-xs text-brand-navy hover:text-brand-red font-medium border border-brand-navy/20 rounded px-2 py-1 hover:bg-brand-blue/10">
                                            Load Default Checklist
                                        </button>
                                    </div>
                                    <p class="mb-3 text-xs text-gray-500">Each row becomes one check item during an inspection.</p>
                                    <div class="space-y-2">
                                        <template x-for="(check, i) in checks" :key="i">
                                            <div class="flex gap-2 items-center">
                                                <input :name="`checklist_json[${i}][category]`" x-model="check.category"
                                                    type="text" list="checklist-categories"
                                                    placeholder="Category…"
                                                    class="w-44 shrink-0 border-gray-300 rounded-md shadow-sm text-sm focus:border-brand-red focus:ring-brand-red">
                                                <input :name="`checklist_json[${i}][text]`" x-model="check.text" type="text"
                                                    placeholder="Describe what to check…"
                                                    class="flex-1 border-gray-300 rounded-md shadow-sm text-sm focus:border-brand-red focus:ring-brand-red">
                                                <button type="button" x-on:click="checks.splice(i, 1)"
                                                    class="shrink-0 w-7 h-7 flex items-center justify-center rounded-full text-red-400 hover:bg-red-50 hover:text-red-600 text-xl leading-none" title="Remove check">×</button>
                                            </div>
                                        </template>
                                    </div>
                                    <button type="button" x-on:click="checks.push({ category: '', text: '' })"
                                        class="mt-2 text-sm text-brand-navy hover:text-brand-red font-medium">+ Add check</button>
                                </div>

                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>Save Changes</x-primary-button>
                            <a href="{{ route('kit-types.index') }}">
                                <x-secondary-button type="button">Cancel</x-secondary-button>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
