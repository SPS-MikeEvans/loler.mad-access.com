<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Kit Types') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 px-4 py-3 bg-green-100 text-green-800 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 px-4 py-3 bg-red-100 text-red-800 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            @php
                $lastRefresh = \Illuminate\Support\Facades\Cache::get('kit_types.refresh_total');
                $refreshDone = $lastRefresh['done'] ?? 0;
                $refreshTotal = $lastRefresh['dispatched'] ?? 14;
                $refreshInProgress = $lastRefresh && $refreshDone < $refreshTotal;
                $refreshPct = $refreshTotal > 0 ? round(($refreshDone / $refreshTotal) * 100) : 100;
            @endphp
            @if ($lastRefresh)
                <div class="mb-4 px-4 py-4 bg-blue-50 border border-blue-100 rounded-lg text-sm"
                     x-data="{ poll: true }"
                     x-init="if ({{ $refreshInProgress ? 'true' : 'false' }}) {
                         let t = setInterval(() => { if (poll) location.reload() }, 10000);
                         setTimeout(() => { clearInterval(t); poll = false }, 300000);
                     }">
                    @if ($refreshInProgress)
                        <div class="flex items-center justify-between mb-2 text-blue-800">
                            <span class="font-medium">Updating equipment list…</span>
                            <span class="text-xs tabular-nums">{{ $refreshDone }}/{{ $refreshTotal }} brands</span>
                        </div>
                        <div class="w-full bg-blue-200 rounded-full h-2 overflow-hidden">
                            <div class="bg-blue-600 h-2 rounded-full transition-all duration-500"
                                 style="width: {{ $refreshPct }}%"></div>
                        </div>
                    @else
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex flex-wrap gap-x-4 gap-y-1 text-blue-800">
                                <span>Last AI refresh: {{ \Carbon\Carbon::parse($lastRefresh['ran_at'])->format('d M Y H:i') }}</span>
                                <span class="text-green-700 font-medium">{{ $lastRefresh['added'] }} new types added</span>
                                <span class="text-gray-600">{{ $lastRefresh['skipped'] }} already existed</span>
                            </div>
                        </div>
                        <div class="w-full bg-green-200 rounded-full h-2 overflow-hidden">
                            <div class="bg-green-500 h-2 rounded-full w-full"></div>
                        </div>
                        @foreach ($lastRefresh['errors'] ?? [] as $err)
                            <p class="mt-2 text-red-600 text-xs">{{ $err }}</p>
                        @endforeach
                    @endif
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6"
                     x-data="{
                        search: '',
                        category: '',
                        selected: [],
                        isSelected(id) { return this.selected.includes(id) },
                        toggle(id) {
                            const i = this.selected.indexOf(id);
                            if (i >= 0) this.selected.splice(i, 1); else this.selected.push(id);
                        },
                        clearSelection() { this.selected = [] },
                        isVisible(name, brand, cat) {
                            const haystack = (name + ' ' + (brand || '') + ' ' + (cat || '')).toLowerCase();
                            return (this.search === '' || haystack.includes(this.search.toLowerCase()))
                                && (this.category === '' || cat === this.category);
                        },
                        selectAllVisible() {
                            const ids = [...document.querySelectorAll('tr[data-kit-type-row]')]
                                .filter(tr => tr.style.display !== 'none' && tr.dataset.id)
                                .map(tr => parseInt(tr.dataset.id));
                            this.selected = ids;
                        }
                     }">

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                        <div class="flex flex-col sm:flex-row gap-3">
                            {{-- Live text search (client-side) --}}
                            <input type="text" x-model="search" placeholder="Search name, brand…"
                                class="border-gray-300 rounded-md shadow-sm text-sm focus:border-brand-red focus:ring-brand-red w-full sm:w-56">
                            {{-- Category filter (client-side) --}}
                            <select x-model="category"
                                class="border-gray-300 rounded-md shadow-sm text-sm focus:border-brand-red focus:ring-brand-red">
                                <option value="">All categories</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <a href="{{ route('kit-types.create') }}">
                                <x-primary-button>Add Kit Type</x-primary-button>
                            </a>
                            @if(auth()->user()->isAdmin())
                                <form method="POST" action="{{ route('kit-types.bulk-edit.form') }}">
                                    @csrf
                                    <template x-for="id in selected" :key="id">
                                        <input type="hidden" name="kit_type_ids[]" :value="id">
                                    </template>
                                    <button type="submit"
                                            x-bind:disabled="selected.length === 0"
                                            class="inline-flex w-full items-center justify-center gap-2 px-4 py-2 rounded-xl bg-brand-navy text-white text-sm font-medium hover:bg-brand-red transition disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-brand-navy">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        Bulk Edit
                                        <span x-show="selected.length > 0" x-cloak
                                              class="ml-1 inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full bg-brand-red text-xs font-semibold"
                                              x-text="selected.length"></span>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('kit-types.ai-refresh') }}">
                                    @csrf
                                    <button type="submit"
                                            onclick="return confirm('Query xAI to find new equipment types for the active brands? Existing records will not be changed.')"
                                            class="inline-flex w-full items-center justify-center gap-2 px-4 py-2 rounded-xl border border-gray-300 bg-white text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition">
                                        <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                        Update Equipment List
                                    </button>
                                </form>
                                <a href="{{ route('kit-brands.index') }}"
                                   class="inline-flex w-full items-center justify-center gap-2 px-4 py-2 rounded-xl border border-gray-300 bg-white text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition">
                                    Manage Brands
                                </a>
                            @endif
                        </div>
                    </div>

                    @if ($kitTypes->isEmpty())
                        <p class="text-gray-500 italic">No kit types added yet.</p>
                    @else
                        @if (auth()->user()->isAdmin())
                            <p class="mb-3 text-xs text-gray-500">
                                Tick one or more rows to bulk-edit inspection price, resource links, interval or the lifts-people flag. The
                                <span class="font-medium text-brand-navy">Bulk Edit</span> button above (and the bar at the bottom) become active once at least one row is selected.
                            </p>
                        @endif
                        @php
                            /** @param string $col @param string $label */
                            $sortLink = function (string $col, string $label) use ($sort, $dir): string {
                                $nextDir = ($sort === $col && $dir === 'asc') ? 'desc' : 'asc';
                                $arrow = $sort === $col ? ($dir === 'asc' ? ' ↑' : ' ↓') : '';
                                $url = route('kit-types.index', ['sort' => $col, 'dir' => $nextDir]);
                                return "<a href=\"{$url}\" class=\"hover:text-gray-900\">{$label}{$arrow}</a>";
                            };
                        @endphp
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        @if (auth()->user()->isAdmin())
                                            <th class="px-3 py-3 w-8">
                                                <button type="button" @click="selectAllVisible()" title="Select all visible"
                                                        class="text-xs text-brand-navy hover:text-brand-red font-medium">all</button>
                                            </th>
                                        @endif
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {!! $sortLink('name', 'Name') !!}
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {!! $sortLink('brand', 'Brand') !!}
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {!! $sortLink('category', 'Category') !!}
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SWL</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {!! $sortLink('interval_months', 'Interval') !!}
                                        </th>
                                        <th class="hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lifts People</th>
                                        <th class="hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Docs</th>
                                        <th class="px-4 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($kitTypes as $type)
                                        <tr class="hover:bg-gray-50"
                                            data-kit-type-row
                                            data-id="{{ $type->id }}"
                                            x-show="
                                                (search === '' || '{{ strtolower(addslashes($type->name . ' ' . $type->brand . ' ' . $type->category)) }}'.includes(search.toLowerCase())) &&
                                                (category === '' || '{{ addslashes($type->category) }}' === category)
                                            ">
                                            @if (auth()->user()->isAdmin())
                                                <td class="px-3 py-3 w-8 align-middle">
                                                    <input type="checkbox" :checked="isSelected({{ $type->id }})"
                                                           @click="toggle({{ $type->id }})"
                                                           class="rounded border-gray-300 text-brand-red focus:ring-brand-red">
                                                </td>
                                            @endif
                                            <td class="px-4 py-3 font-medium text-gray-900 text-sm">
                                                {{ $type->name }}
                                                @if ($type->ai_suggested)
                                                    <span class="ml-1 px-1.5 py-0.5 text-xs rounded bg-purple-100 text-purple-700">AI suggested</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-gray-600 text-sm">{{ $type->brand ?? '—' }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                                @if ($type->category)
                                                    <span class="px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-700 rounded-full">{{ $type->category }}</span>
                                                @else
                                                    <span class="text-gray-400">—</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-gray-600 text-sm">{{ $type->swl_description ?? '—' }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-gray-600 text-sm">{{ $type->interval_months }} mo</td>
                                            <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap">
                                                @if ($type->lifts_people)
                                                    <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">Yes</span>
                                                @else
                                                    <span class="px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded-full">No</span>
                                                @endif
                                            </td>
                                            <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm">
                                                <div class="flex gap-2">
                                                    @if ($type->spec_pdf_path)
                                                        <a href="{{ Storage::url($type->spec_pdf_path) }}" target="_blank" rel="noopener"
                                                           title="Product Specification PDF"
                                                           class="inline-flex items-center gap-1 text-xs text-red-600 hover:text-red-800 bg-red-50 rounded px-1.5 py-0.5">
                                                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/></svg>
                                                            Spec
                                                        </a>
                                                    @endif
                                                    @if ($type->inspection_pdf_path)
                                                        <a href="{{ Storage::url($type->inspection_pdf_path) }}" target="_blank" rel="noopener"
                                                           title="Inspection Procedure PDF"
                                                           class="inline-flex items-center gap-1 text-xs text-red-600 hover:text-red-800 bg-red-50 rounded px-1.5 py-0.5">
                                                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/></svg>
                                                            Insp
                                                        </a>
                                                    @endif
                                                    @if (!$type->spec_pdf_path && !$type->inspection_pdf_path)
                                                        <span class="text-gray-400">—</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-right text-sm space-x-4">
                                                <a href="{{ route('kit-types.edit', $type) }}"
                                                   class="text-amber-600 hover:text-amber-900">Edit</a>
                                                @if (auth()->user()->isAdmin())
                                                    <button type="button"
                                                          class="text-red-600 hover:text-red-900"
                                                          x-data
                                                          x-on:click="$dispatch('open-modal', '{{ $deleteConfirmations[$type->id]->modalName }}')">
                                                        Delete
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if (auth()->user()->isAdmin())
                            @foreach ($kitTypes as $type)
                                <x-confirmed-action-modal
                                    :name="$deleteConfirmations[$type->id]->modalName"
                                    title="Delete Kit Type"
                                    :message="'This permanently deletes '.$type->name.'. Type the phrase below to continue.'"
                                    :phrase="$deleteConfirmations[$type->id]->phrase"
                                    :action="route('kit-types.destroy', $type)"
                                    method="DELETE"
                                    submit-label="Delete Kit Type"
                                    :password-confirm="true"
                                />
                            @endforeach

                            {{-- Sticky bulk-edit action bar --}}
                            <div x-show="selected.length > 0"
                                 x-cloak
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 translate-y-2"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 class="fixed bottom-4 inset-x-4 sm:inset-x-auto sm:left-1/2 sm:-translate-x-1/2 z-40 max-w-lg mx-auto sm:mx-0 bg-brand-navy text-white shadow-lg rounded-xl px-4 py-3 flex items-center justify-between gap-4">
                                <span class="text-sm">
                                    <span class="font-semibold" x-text="selected.length"></span>
                                    <span x-text="selected.length === 1 ? 'kit type selected' : 'kit types selected'"></span>
                                </span>
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="clearSelection()"
                                            class="text-xs text-white/80 hover:text-white underline">Clear</button>
                                    <form method="POST" action="{{ route('kit-types.bulk-edit.form') }}">
                                        @csrf
                                        <template x-for="id in selected" :key="id">
                                            <input type="hidden" name="kit_type_ids[]" :value="id">
                                        </template>
                                        <button type="submit"
                                                class="px-3 py-1.5 rounded-lg bg-brand-red text-white text-sm font-medium hover:bg-red-700 transition">
                                            Bulk Edit Selected →
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
