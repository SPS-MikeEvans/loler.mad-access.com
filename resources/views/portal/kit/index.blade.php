<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            My Equipment
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-7xl mx-auto mobile-shell">

            @if (session('success'))
                <div class="mb-4 px-4 py-3 bg-green-100 text-green-800 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mobile-card overflow-hidden sm:rounded-lg">
                <div class="mobile-card-body overflow-x-hidden" x-data="{ search: '' }">
                    <div class="grid grid-cols-1 gap-3 mb-4 sm:flex sm:items-center sm:justify-between">
                        <h3 class="min-w-0 break-words pr-0 text-lg font-medium text-gray-900 sm:pr-4">My Equipment</h3>
                        <a href="{{ route('portal.kit.create') }}" class="w-full sm:w-auto">
                            <x-primary-button class="w-full justify-center sm:w-auto">Add Equipment</x-primary-button>
                        </a>
                    </div>

                    @if ($kitItems->isEmpty())
                        <p class="text-gray-500 italic">No equipment added yet. <a href="{{ route('portal.kit.create') }}" class="text-brand-navy underline">Add your first item.</a></p>
                    @else
                        <div class="mb-4">
                            <input x-model="search" type="search"
                                placeholder="Filter by type, asset tag, serial number or status…"
                                class="block w-full border-gray-300 rounded-xl shadow-sm text-sm focus:border-brand-red focus:ring-brand-red sm:w-80" />
                        </div>

                        {{-- Mobile card list --}}
                        <div class="block space-y-3 sm:hidden">
                            @foreach ($kitItems as $item)
                                @php
                                    $statusColour = match($item->status) {
                                        'in_service'     => 'bg-green-100 text-green-800',
                                        'inspection_due' => 'bg-yellow-100 text-yellow-800',
                                        'quarantined'    => 'bg-red-100 text-red-800',
                                        'retired'        => 'bg-gray-100 text-gray-600',
                                        default          => 'bg-gray-100 text-gray-600',
                                    };
                                @endphp
                                <details class="mobile-list-card overflow-hidden group"
                                    x-show="search === ''
                                        || '{{ strtolower($item->typeName()) }}'.includes(search.toLowerCase())
                                        || '{{ strtolower($item->asset_tag ?? '') }}'.includes(search.toLowerCase())
                                        || '{{ strtolower($item->serial_no ?? '') }}'.includes(search.toLowerCase())
                                        || '{{ strtolower($item->status) }}'.includes(search.toLowerCase())">
                                    <summary class="list-none cursor-pointer px-4 py-4">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="mobile-meta-label">Equipment Type</p>
                                                <p class="mt-1 break-words text-base font-semibold text-slate-900">{{ $item->typeName() }}</p>
                                            </div>
                                            <svg class="mt-1 h-5 w-5 shrink-0 text-slate-400 transition group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </div>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            @if ($item->pending_review)
                                                <span class="mobile-chip bg-blue-100 text-blue-700">Pending Review</span>
                                            @else
                                                <span class="mobile-chip {{ $statusColour }}">{{ ucfirst(str_replace('_', ' ', $item->status)) }}</span>
                                            @endif
                                            @if ($item->flagged_for_inspection)
                                                <span class="mobile-chip bg-orange-100 text-orange-700">Flagged for Inspection</span>
                                            @endif
                                        </div>
                                    </summary>

                                    <div class="border-t border-slate-200 px-4 pb-4 pt-4 space-y-4">
                                        <div class="mobile-meta-grid">
                                            <div class="mobile-meta-item">
                                                <p class="mobile-meta-label">Asset / Serial</p>
                                                <p class="mobile-meta-value">{{ $item->asset_tag ?? $item->serial_no ?? '—' }}</p>
                                            </div>
                                            <div class="mobile-meta-item">
                                                <p class="mobile-meta-label">Next Inspection Due</p>
                                                <p class="mobile-meta-value {{ $item->next_inspection_due?->isPast() ? 'text-red-600' : '' }}">
                                                    {{ $item->next_inspection_due?->format('d M Y') ?? '—' }}
                                                </p>
                                            </div>
                                            <div class="mobile-meta-item">
                                                <p class="mobile-meta-label">Manufacturer</p>
                                                <p class="mobile-meta-value">{{ $item->manufacturer ?? '—' }}</p>
                                            </div>
                                            <div class="mobile-meta-item">
                                                <p class="mobile-meta-label">Model</p>
                                                <p class="mobile-meta-value">{{ $item->model ?? '—' }}</p>
                                            </div>
                                        </div>

                                        <div class="mobile-action-group">
                                            <a href="{{ route('portal.kit.show', $item) }}" class="mobile-action-link">View Details</a>
                                        </div>
                                    </div>
                                </details>
                            @endforeach
                        </div>

                        {{-- Desktop table --}}
                        <div class="hidden sm:block overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asset / Serial</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Next Inspection Due</th>
                                        <th class="px-6 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($kitItems as $item)
                                        @php
                                            $statusColour = match($item->status) {
                                                'in_service'     => 'bg-green-100 text-green-800',
                                                'inspection_due' => 'bg-yellow-100 text-yellow-800',
                                                'quarantined'    => 'bg-red-100 text-red-800',
                                                'retired'        => 'bg-gray-100 text-gray-600',
                                                default          => 'bg-gray-100 text-gray-600',
                                            };
                                        @endphp
                                        <tr class="hover:bg-gray-50"
                                            x-show="search === ''
                                                || '{{ strtolower($item->typeName()) }}'.includes(search.toLowerCase())
                                                || '{{ strtolower($item->asset_tag ?? '') }}'.includes(search.toLowerCase())
                                                || '{{ strtolower($item->serial_no ?? '') }}'.includes(search.toLowerCase())
                                                || '{{ strtolower($item->status) }}'.includes(search.toLowerCase())">
                                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">{{ $item->typeName() }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-gray-600">{{ $item->asset_tag ?? $item->serial_no ?? '—' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex flex-wrap gap-1">
                                                    @if ($item->pending_review)
                                                        <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700">Pending Review</span>
                                                    @else
                                                        <span class="px-2 py-1 text-xs rounded-full {{ $statusColour }}">
                                                            {{ ucfirst(str_replace('_', ' ', $item->status)) }}
                                                        </span>
                                                    @endif
                                                    @if ($item->flagged_for_inspection)
                                                        <span class="px-2 py-1 text-xs rounded-full bg-orange-100 text-orange-700">Flagged</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap {{ $item->next_inspection_due?->isPast() ? 'text-red-600 font-semibold' : 'text-gray-600' }}">
                                                {{ $item->next_inspection_due?->format('d M Y') ?? '—' }}
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <a href="{{ route('portal.kit.show', $item) }}"
                                                   class="text-sm text-brand-navy hover:text-brand-red font-medium">View</a>
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
