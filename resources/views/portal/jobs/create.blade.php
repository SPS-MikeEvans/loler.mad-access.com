<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Request Inspection — Step 1 of 3
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-3xl mx-auto mobile-shell">
            <div class="mobile-card sm:rounded-lg">
                <div class="mobile-card-body">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Pick Items</h3>
                    <p class="text-sm text-gray-600 mb-4">Select the equipment you'd like inspected. Items are grouped by your kit groups for convenience.</p>

                    @if ($errors->any())
                        <div class="mb-4 px-4 py-3 bg-red-100 text-red-800 rounded-lg text-sm">
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('portal.jobs.date') }}" class="space-y-4">
                        @csrf

                        @if ($itemsByGroup->isEmpty())
                            <p class="text-sm text-gray-500 italic">No equipment available to inspect.</p>
                        @else
                            @foreach ($itemsByGroup as $groupKey => $groupItems)
                                @php
                                    $groupName = $groupKey === 'none' ? 'Ungrouped' : ($kitGroups[$groupKey]->name ?? 'Group');
                                @endphp
                                <details class="border border-gray-200 rounded-lg" open>
                                    <summary class="cursor-pointer px-4 py-3 font-medium text-gray-900">
                                        {{ $groupName }} ({{ $groupItems->count() }})
                                    </summary>
                                    <div class="px-4 pb-3 space-y-2 border-t border-gray-100">
                                        @foreach ($groupItems as $item)
                                            <label class="flex items-start gap-3 px-2 py-1.5 hover:bg-gray-50 rounded cursor-pointer">
                                                <input type="checkbox" name="kit_item_ids[]" value="{{ $item->id }}"
                                                    @checked(in_array($item->id, old('kit_item_ids', $selectedIds), false))
                                                    class="mt-0.5 rounded border-gray-300 text-brand-red focus:ring-brand-red" />
                                                <span class="text-sm">
                                                    <span class="font-medium text-gray-900">{{ $item->typeName() }}</span>
                                                    <span class="text-gray-500">— {{ $item->asset_tag ?? $item->serial_no ?? "#{$item->id}" }}</span>
                                                    @if ($item->next_inspection_due)
                                                        <span class="block text-xs text-gray-500">Next due: {{ $item->next_inspection_due->format('d M Y') }}</span>
                                                    @endif
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </details>
                            @endforeach
                        @endif

                        <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-3 pt-4">
                            <a href="{{ route('portal.jobs.index') }}" class="text-sm text-brand-navy hover:text-brand-red text-center">Cancel</a>
                            <x-primary-button>Next: Choose Date →</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
