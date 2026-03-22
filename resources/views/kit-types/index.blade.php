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

            @php $lastRefresh = \Illuminate\Support\Facades\Cache::get('kit_types.refresh_total'); @endphp
            @if ($lastRefresh)
                <div class="mb-4 px-4 py-3 bg-blue-50 border border-blue-100 rounded-lg text-sm"
                     x-data="{ poll: true }"
                     x-init="if ({{ $lastRefresh['done'] ?? 0 }} < {{ $lastRefresh['dispatched'] ?? 14 }}) {
                         let t = setInterval(() => { if (poll) location.reload() }, 10000);
                         setTimeout(() => { clearInterval(t); poll = false }, 300000);
                     }">
                    <div class="flex flex-wrap gap-x-4 gap-y-1 text-blue-800">
                        @if (($lastRefresh['done'] ?? 0) < ($lastRefresh['dispatched'] ?? 14))
                            <span class="font-medium">Refreshing… ({{ $lastRefresh['done'] ?? 0 }}/{{ $lastRefresh['dispatched'] ?? 14 }} brands done)</span>
                        @else
                            <span>Last AI refresh: {{ \Carbon\Carbon::parse($lastRefresh['ran_at'])->format('d M Y H:i') }}</span>
                            <span class="text-green-700 font-medium">{{ $lastRefresh['added'] }} new types added</span>
                            <span>{{ $lastRefresh['skipped'] }} already existed</span>
                        @endif
                        @foreach ($lastRefresh['errors'] ?? [] as $err)
                            <span class="text-red-600">{{ $err }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6" x-data="{ search: '', category: '' }">

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
                                <form method="POST" action="{{ route('kit-types.ai-refresh') }}">
                                    @csrf
                                    <button type="submit"
                                            onclick="return confirm('Query xAI to find new equipment types for 14 brands? Existing records will not be changed.')"
                                            class="inline-flex w-full items-center justify-center gap-2 px-4 py-2 rounded-xl border border-gray-300 bg-white text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition">
                                        <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                        Update Equipment List
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    @if ($kitTypes->isEmpty())
                        <p class="text-gray-500 italic">No kit types added yet.</p>
                    @else
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
                                            x-show="
                                                (search === '' || '{{ strtolower(addslashes($type->name . ' ' . $type->brand . ' ' . $type->category)) }}'.includes(search.toLowerCase())) &&
                                                (category === '' || '{{ addslashes($type->category) }}' === category)
                                            ">
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
                                                <form method="POST"
                                                      action="{{ route('kit-types.destroy', $type) }}"
                                                      class="inline"
                                                      x-data
                                                      x-on:submit.prevent="
                                                          $el.querySelector('button').textContent !== 'Confirm?' ?
                                                              ($el.querySelector('button').textContent = 'Confirm?') :
                                                              $el.submit()
                                                      ">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
