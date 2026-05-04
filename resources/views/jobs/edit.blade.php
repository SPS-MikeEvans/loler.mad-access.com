<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Edit Job — {{ $job->job_number }}
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-3xl mx-auto mobile-shell">
            <div class="mobile-card sm:rounded-lg">
                <div class="mobile-card-body">

                    @if (session('success'))
                        <div class="mb-4 px-4 py-3 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>
                    @endif

                    <form method="POST" action="{{ route('jobs.update', $job) }}">
                        @csrf
                        @method('PUT')

                        {{-- Notes --}}
                        <div class="mb-5">
                            <x-input-label for="notes" value="Notes (optional)" />
                            <textarea
                                name="notes"
                                id="notes"
                                rows="3"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-red focus:border-brand-red"
                            >{{ old('notes', $job->notes) }}</textarea>
                        </div>

                        {{-- Kit items --}}
                        <div class="mb-6">
                            <x-input-label value="Kit Items" />
                            <p class="text-sm text-gray-500 mb-3">Select items to include in this job. Items with inspections linked to this job must stay selected.</p>

                            @php $selectedIds = $job->kitItems->pluck('id')->toArray(); @endphp

                            @if ($clientKitItems->isEmpty())
                                <p class="text-sm text-gray-400 italic">No active kit items for this client.</p>
                            @else
                                <div class="space-y-2 max-h-80 overflow-y-auto border border-gray-200 rounded-lg p-3">
                                    @foreach ($clientKitItems as $index => $item)
                                        @php
                                            $isSelected = in_array($item->id, $selectedIds);
                                            $isLocked = in_array($item->id, $lockedKitItemIds);
                                            $pivot = $job->kitItems->find($item->id)?->pivot;
                                        @endphp
                                        <div class="p-2 rounded hover:bg-gray-50">
                                            <label class="flex items-start gap-3 cursor-pointer">
                                                @if ($isLocked)
                                                    <input type="hidden" name="kit_item_ids[]" value="{{ $item->id }}">
                                                @endif
                                                <input
                                                    type="checkbox"
                                                    name="kit_item_ids[]"
                                                    value="{{ $item->id }}"
                                                    @checked($isSelected)
                                                    @disabled($isLocked)
                                                    class="mt-0.5 rounded border-gray-300 text-brand-red focus:ring-brand-red"
                                                    id="item_{{ $item->id }}"
                                                >
                                                <span class="text-sm text-gray-800">
                                                    {{ $item->typeName() }}
                                                    @if ($item->asset_tag || $item->serial_no)
                                                        <span class="text-gray-400">- {{ $item->asset_tag ?? $item->serial_no }}</span>
                                                    @endif
                                                    @if ($isLocked)
                                                        <span class="ml-2 text-xs font-medium text-amber-600">inspection linked</span>
                                                    @endif
                                                </span>
                                            </label>
                                            @if ($isSelected)
                                                <div class="mt-1 ml-7">
                                                    <input
                                                        type="text"
                                                        name="condition_notes[{{ $item->id }}]"
                                                        value="{{ old("condition_notes.{$item->id}", $pivot?->condition_notes) }}"
                                                        placeholder="Condition notes at drop-off (optional)"
                                                        class="block w-full border-gray-200 rounded text-xs text-gray-600 focus:ring-brand-red focus:border-brand-red"
                                                    >
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <div class="mt-3">
                                <a href="{{ route('clients.kit-items.create', [$job->client, 'return_to_job' => $job->id]) }}" class="text-sm text-brand-navy hover:text-brand-red">
                                    + Register a new kit item and add it to this job
                                </a>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>Save Changes</x-primary-button>
                            <a href="{{ route('jobs.show', $job) }}" class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
