<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ $kitItem->kitType->name }}
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-4xl mx-auto mobile-shell space-y-6">

            @if (session('success'))
                <div class="px-4 py-3 bg-green-100 text-green-800 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Item details --}}
            <div class="mobile-card overflow-hidden sm:rounded-lg">
                <div class="mobile-card-body">
                    <div class="flex items-start justify-between gap-4 mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ $kitItem->kitType->name }}</h3>
                            <p class="text-sm text-gray-500">{{ $kitItem->kitType->category }}</p>
                        </div>
                        @php
                            $statusColour = match($kitItem->status) {
                                'in_service'     => 'bg-green-100 text-green-800',
                                'inspection_due' => 'bg-yellow-100 text-yellow-800',
                                'quarantined'    => 'bg-red-100 text-red-800',
                                'retired'        => 'bg-gray-100 text-gray-600',
                                default          => 'bg-gray-100 text-gray-600',
                            };
                        @endphp
                        <div class="flex flex-wrap gap-2 shrink-0">
                            <span class="px-2 py-1 text-xs rounded-full {{ $statusColour }}">
                                {{ ucfirst(str_replace('_', ' ', $kitItem->status)) }}
                            </span>
                            @if ($kitItem->flagged_for_inspection)
                                <span class="px-2 py-1 text-xs rounded-full bg-orange-100 text-orange-700">Flagged for Inspection</span>
                            @endif
                        </div>
                    </div>

                    <div class="mobile-meta-grid mb-4">
                        <div class="mobile-meta-item">
                            <p class="mobile-meta-label">Asset Tag</p>
                            <p class="mobile-meta-value">{{ $kitItem->asset_tag ?? '—' }}</p>
                        </div>
                        <div class="mobile-meta-item">
                            <p class="mobile-meta-label">Serial Number</p>
                            <p class="mobile-meta-value">{{ $kitItem->serial_no ?? '—' }}</p>
                        </div>
                        <div class="mobile-meta-item">
                            <p class="mobile-meta-label">Manufacturer</p>
                            <p class="mobile-meta-value">{{ $kitItem->manufacturer ?? '—' }}</p>
                        </div>
                        <div class="mobile-meta-item">
                            <p class="mobile-meta-label">Model</p>
                            <p class="mobile-meta-value">{{ $kitItem->model ?? '—' }}</p>
                        </div>
                        <div class="mobile-meta-item">
                            <p class="mobile-meta-label">Next Inspection Due</p>
                            <p class="mobile-meta-value {{ $kitItem->next_inspection_due?->isPast() ? 'text-red-600 font-semibold' : '' }}">
                                {{ $kitItem->next_inspection_due?->format('d M Y') ?? '—' }}
                            </p>
                        </div>
                        <div class="mobile-meta-item">
                            <p class="mobile-meta-label">Lifts People</p>
                            <p class="mobile-meta-value">{{ $kitItem->lifting_people ? 'Yes' : 'No' }}</p>
                        </div>
                    </div>

                    @if ($kitItem->flagged_for_inspection && $kitItem->flag_notes)
                        <div class="mb-4 p-3 bg-orange-50 border border-orange-200 rounded-lg text-sm text-orange-800">
                            <p class="font-medium mb-1">Your inspection notes:</p>
                            <p>{{ $kitItem->flag_notes }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Flag for inspection --}}
            @if (! $kitItem->pending_review && $kitItem->status !== 'retired')
                <div class="mobile-card overflow-hidden sm:rounded-lg">
                    <div class="mobile-card-body">
                        <h3 class="text-base font-semibold text-gray-900 mb-2">
                            {{ $kitItem->flagged_for_inspection ? 'Remove Inspection Flag' : 'Flag for Inspection' }}
                        </h3>

                        @if (! $kitItem->flagged_for_inspection)
                            <p class="text-sm text-gray-600 mb-4">
                                Flag this item to let our team know it needs a LOLER Thorough Examination. You can add notes — for example, if the inspection is urgent or there is visible damage.
                            </p>
                            <form method="POST" action="{{ route('portal.kit.flag', $kitItem) }}" x-data="{ expanded: false }">
                                @csrf
                                @method('PATCH')

                                <div class="mb-4">
                                    <button type="button" @click="expanded = !expanded"
                                            class="text-sm text-brand-navy underline focus:outline-none">
                                        <span x-text="expanded ? 'Remove notes' : 'Add notes (optional)'"></span>
                                    </button>
                                    <div x-show="expanded" x-cloak class="mt-2">
                                        <textarea name="flag_notes" rows="3" maxlength="1000"
                                                  placeholder="e.g. Upcoming job on 10 April — inspection required before then."
                                                  class="block w-full border-gray-300 rounded-xl shadow-sm text-sm focus:border-brand-red focus:ring-brand-red">{{ old('flag_notes') }}</textarea>
                                    </div>
                                </div>

                                <x-primary-button class="bg-orange-500 hover:bg-orange-600 focus:ring-orange-400">
                                    Flag for Inspection
                                </x-primary-button>
                            </form>
                        @else
                            <p class="text-sm text-gray-600 mb-4">
                                This item is currently flagged. Our team has been notified. Remove the flag if the inspection is no longer required.
                            </p>
                            <form method="POST" action="{{ route('portal.kit.flag', $kitItem) }}">
                                @csrf
                                @method('PATCH')
                                <x-secondary-button type="submit">Remove Flag</x-secondary-button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Inspection history --}}
            <div class="mobile-card overflow-hidden sm:rounded-lg">
                <div class="mobile-card-body">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Inspection History</h3>

                    @if ($kitItem->inspections->isEmpty())
                        <p class="text-sm text-gray-500 italic">No completed inspections yet.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Result</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Next Due</th>
                                        <th class="px-4 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($kitItem->inspections as $inspection)
                                        <tr>
                                            <td class="px-4 py-3 whitespace-nowrap text-gray-700">
                                                {{ $inspection->inspection_date?->format('d M Y') ?? '—' }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                @php
                                                    $resultColour = match($inspection->overall_status) {
                                                        'pass'        => 'bg-green-100 text-green-800',
                                                        'fail'        => 'bg-red-100 text-red-800',
                                                        'conditional' => 'bg-yellow-100 text-yellow-800',
                                                        default       => 'bg-gray-100 text-gray-600',
                                                    };
                                                @endphp
                                                <span class="px-2 py-1 text-xs rounded-full {{ $resultColour }}">
                                                    {{ ucfirst($inspection->overall_status ?? '—') }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-gray-600">
                                                {{ $inspection->next_due_date?->format('d M Y') ?? '—' }}
                                            </td>
                                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                                @if ($inspection->pdf_path)
                                                    <a href="{{ route('portal.inspections.pdf', $inspection) }}"
                                                       target="_blank"
                                                       class="text-brand-navy hover:text-brand-red text-xs font-medium">
                                                        Download Certificate
                                                    </a>
                                                @else
                                                    <span class="text-xs text-gray-400">No certificate</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Retire --}}
            @if (! $kitItem->pending_review && $kitItem->status !== 'retired')
                <div class="mobile-card overflow-hidden sm:rounded-lg border border-red-100">
                    <div class="mobile-card-body">
                        <h3 class="text-base font-semibold text-gray-900 mb-2">Retire This Item</h3>
                        <p class="text-sm text-gray-600 mb-4">
                            Mark this item as retired if it is no longer in service. This action cannot be undone from the portal — contact us if you need to reinstate it.
                        </p>
                        <form method="POST" action="{{ route('portal.kit.retire', $kitItem) }}"
                              x-data="{ confirmed: false }"
                              x-on:submit.prevent="confirmed ? $el.submit() : (confirmed = true)">
                            @csrf
                            @method('PATCH')
                            <x-danger-button type="submit" x-text="confirmed ? 'Tap again to confirm retirement' : 'Retire Item'"></x-danger-button>
                        </form>
                    </div>
                </div>
            @endif

            <div class="px-1">
                <a href="{{ route('portal.kit.index') }}" class="text-sm text-brand-navy hover:text-brand-red">← Back to My Equipment</a>
            </div>

        </div>
    </div>
</x-app-layout>
