<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Kit List — {{ $client->name }}
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-7xl mx-auto mobile-shell">

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

            <div class="mobile-card overflow-hidden sm:rounded-lg">
                <div class="mobile-card-body" x-data="{ search: '' }">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Equipment — {{ $client->name }}</h3>
                        <a href="{{ route('clients.kit-items.create', $client) }}" class="w-full sm:w-auto">
                            <x-primary-button class="w-full justify-center sm:w-auto">Add Kit Item</x-primary-button>
                        </a>
                    </div>

                    @if ($kitItems->isEmpty())
                        <p class="text-gray-500 italic">No kit items added for this client yet.</p>
                    @else
                        <div class="mb-4">
                            <input x-model="search" type="search"
                                placeholder="Filter by type, asset tag, serial number or status…"
                                class="block w-full border-gray-300 rounded-xl shadow-sm text-sm focus:border-brand-red focus:ring-brand-red sm:w-80" />
                        </div>

                        <div class="space-y-3 sm:hidden">
                            @foreach ($kitItems as $item)
                                @php
                                    $statusColour = match($item->status) {
                                        'in_service' => 'bg-green-100 text-green-800',
                                        'inspection_due' => 'bg-yellow-100 text-yellow-800',
                                        'quarantined' => 'bg-red-100 text-red-800',
                                        'retired' => 'bg-gray-100 text-gray-600',
                                        default => 'bg-gray-100 text-gray-600',
                                    };
                                @endphp
                                <div class="mobile-list-card"
                                    x-show="search === ''
                                        || '{{ strtolower($item->kitType->name) }}'.includes(search.toLowerCase())
                                        || '{{ strtolower($item->asset_tag ?? '') }}'.includes(search.toLowerCase())
                                        || '{{ strtolower($item->serial_no ?? '') }}'.includes(search.toLowerCase())
                                        || '{{ strtolower($item->status) }}'.includes(search.toLowerCase())">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <a href="{{ route('clients.kit-items.show', [$client, $item]) }}" class="block text-base font-semibold text-slate-900 hover:text-brand-red">{{ $item->kitType->name }}</a>
                                            <p class="mt-1 text-sm text-slate-600">{{ $item->asset_tag ?? $item->serial_no ?? 'No asset or serial' }}</p>
                                        </div>
                                        <span class="mobile-chip {{ $statusColour }}">{{ ucfirst(str_replace('_', ' ', $item->status)) }}</span>
                                    </div>

                                    <div class="mt-4 mobile-meta-grid">
                                        <div class="mobile-meta-item">
                                            <p class="mobile-meta-label">Next Inspection Due</p>
                                            <p class="mobile-meta-value {{ $item->next_inspection_due?->isPast() ? 'text-red-600' : '' }}">{{ $item->next_inspection_due?->format('d M Y') ?? '—' }}</p>
                                        </div>
                                        <div class="mobile-meta-item">
                                            <p class="mobile-meta-label">Inspection Access</p>
                                            <p class="mobile-meta-value">Desktop and mobile options available</p>
                                        </div>
                                    </div>

                                    <details class="mt-4 rounded-xl border border-slate-200 bg-slate-50">
                                        <summary class="cursor-pointer list-none px-4 py-3 text-sm font-semibold text-brand-navy">Actions</summary>
                                        <div class="border-t border-slate-200 px-3 py-3 mobile-action-group">
                                            <a href="{{ route('clients.kit-items.inspections.create', [$client, $item]) }}" class="mobile-action-link">Inspect</a>
                                            <a href="{{ route('clients.kit-items.show', [$client, $item]) }}" class="mobile-action-link">View</a>
                                            <a href="{{ route('clients.kit-items.edit', [$client, $item]) }}" class="mobile-action-link">Edit</a>
                                            <form method="POST" action="{{ route('clients.kit-items.destroy', [$client, $item]) }}" class="w-full">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="mobile-action-link w-full border-red-200 text-red-600 hover:border-red-300 hover:bg-red-50" onclick="return confirm('Delete this kit item?')">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </details>
                                </div>
                            @endforeach
                        </div>

                        <div class="hidden sm:block overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asset / Serial</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="hidden sm:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Next Inspection Due</th>
                                        <th class="px-6 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($kitItems as $item)
                                        <tr class="hover:bg-gray-50"
                                            x-show="search === ''
                                                || '{{ strtolower($item->kitType->name) }}'.includes(search.toLowerCase())
                                                || '{{ strtolower($item->asset_tag ?? '') }}'.includes(search.toLowerCase())
                                                || '{{ strtolower($item->serial_no ?? '') }}'.includes(search.toLowerCase())
                                                || '{{ strtolower($item->status) }}'.includes(search.toLowerCase())">
                                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">{{ $item->kitType->name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                                                {{ $item->asset_tag ?? $item->serial_no ?? '—' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @php
                                                    $statusColour = match($item->status) {
                                                        'in_service' => 'bg-green-100 text-green-800',
                                                        'inspection_due' => 'bg-yellow-100 text-yellow-800',
                                                        'quarantined' => 'bg-red-100 text-red-800',
                                                        'retired' => 'bg-gray-100 text-gray-600',
                                                        default => 'bg-gray-100 text-gray-600',
                                                    };
                                                @endphp
                                                <span class="px-2 py-1 text-xs rounded-full {{ $statusColour }}">
                                                    {{ ucfirst(str_replace('_', ' ', $item->status)) }}
                                                </span>
                                            </td>
                                            <td class="hidden sm:table-cell px-6 py-4 whitespace-nowrap {{ $item->next_inspection_due?->isPast() ? 'text-red-600 font-semibold' : 'text-gray-600' }}">
                                                {{ $item->next_inspection_due?->format('d M Y') ?? '—' }}
                                            </td>
                                            <td class="px-4 py-4 text-right text-sm" x-data="{
                                                open: false,
                                                dropX: 0,
                                                dropY: 0,
                                                toggle(event) {
                                                    const rect = event.currentTarget.getBoundingClientRect();
                                                    this.dropX = window.innerWidth - rect.right;
                                                    this.dropY = rect.bottom + 4;
                                                    this.open = !this.open;
                                                }
                                            }">
                                                <div class="relative inline-block text-left">
                                                    <button @click="toggle($event)"
                                                            type="button"
                                                            class="inline-flex items-center justify-center w-8 h-8 rounded-full text-gray-500 hover:text-brand-navy hover:bg-gray-100 focus:outline-none transition">
                                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4z"/>
                                                        </svg>
                                                    </button>

                                                    <template x-teleport="body">
                                                    <div x-show="open"
                                                         @click.outside="open = false"
                                                         x-transition:enter="transition ease-out duration-100"
                                                         x-transition:enter-start="opacity-0 scale-95"
                                                         x-transition:enter-end="opacity-100 scale-100"
                                                         x-transition:leave="transition ease-in duration-75"
                                                         x-transition:leave-start="opacity-100 scale-100"
                                                         x-transition:leave-end="opacity-0 scale-95"
                                                         :style="`position: fixed; right: ${dropX}px; top: ${dropY}px; z-index: 9999;`"
                                                         class="w-44 bg-white rounded-lg shadow-lg ring-1 ring-black/5 divide-y divide-gray-100"
                                                         style="display: none;">
                                                        <div class="py-1">
                                                            <a href="{{ route('clients.kit-items.inspections.create', [$client, $item]) }}"
                                                               class="flex items-center gap-2 px-4 py-2.5 text-sm text-green-700 hover:bg-green-50 font-medium">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                                                Inspect
                                                            </a>
                                                            <a href="{{ route('clients.kit-items.show', [$client, $item]) }}"
                                                               class="flex items-center gap-2 px-4 py-2.5 text-sm text-brand-navy hover:bg-gray-50">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                                View
                                                            </a>
                                                            <a href="{{ route('clients.kit-items.edit', [$client, $item]) }}"
                                                               class="flex items-center gap-2 px-4 py-2.5 text-sm text-amber-600 hover:bg-amber-50">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                                Edit
                                                            </a>
                                                        </div>
                                                        <div class="py-1">
                                                            <form method="POST"
                                                                  action="{{ route('clients.kit-items.destroy', [$client, $item]) }}"
                                                                  x-data="{ confirmed: false }"
                                                                  x-on:submit.prevent="confirmed ? $el.submit() : (confirmed = true)">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit"
                                                                        class="flex items-center gap-2 w-full px-4 py-2.5 text-sm text-red-600 hover:bg-red-50">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                                    <span x-text="confirmed ? 'Tap again to confirm' : 'Delete'"></span>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                    </template>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <div class="mt-6">
                        <a href="{{ route('clients.show', $client) }}" class="text-sm text-brand-navy hover:text-brand-red">← Back to {{ $client->name }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
