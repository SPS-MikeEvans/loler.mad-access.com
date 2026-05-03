<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Preview Bulk Edit
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @php
                $action = $request->input('action');
                $actionLabels = [
                    'set_price' => 'Set inspection price',
                    'adjust_price_amount' => 'Adjust price by amount',
                    'adjust_price_percent' => 'Adjust price by percentage',
                    'add_resource_link' => 'Add resource link',
                    'remove_resource_link' => 'Remove resource link',
                    'set_interval_months' => 'Set inspection interval',
                    'set_lifts_people' => 'Set lifts-people flag',
                ];
                $changingCount = $diffs->where('changes.will_change', true)->count();
                $skippedCount = $diffs->count() - $changingCount;
            @endphp

            <div class="bg-white shadow-sm rounded-lg p-6">
                <p class="text-sm text-gray-600">Action</p>
                <p class="mt-1 text-base font-semibold text-gray-900">{{ $actionLabels[$action] ?? $action }}</p>

                <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs uppercase text-gray-500">Selected</p>
                        <p class="mt-1 text-lg font-semibold text-gray-900">{{ $diffs->count() }}</p>
                    </div>
                    <div class="bg-green-50 rounded-lg p-3">
                        <p class="text-xs uppercase text-green-700">Will change</p>
                        <p class="mt-1 text-lg font-semibold text-green-800">{{ $changingCount }}</p>
                    </div>
                    <div class="bg-amber-50 rounded-lg p-3">
                        <p class="text-xs uppercase text-amber-700">Skipped (no change)</p>
                        <p class="mt-1 text-lg font-semibold text-amber-800">{{ $skippedCount }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kit Type</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Field</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Before</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">After</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($diffs as $row)
                                @php
                                    $type = $row['type'];
                                    $c = $row['changes'];
                                    $field = $c['field'];
                                @endphp
                                <tr class="{{ $c['will_change'] ? '' : 'bg-amber-50/50' }}">
                                    <td class="px-4 py-3 text-gray-900 font-medium">
                                        {{ $type->name }}
                                        @if ($type->brand)
                                            <span class="text-gray-400 text-xs">— {{ $type->brand }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">{{ $field }}</td>
                                    <td class="px-4 py-3 text-gray-700">
                                        @if (in_array($field, ['inspection_price'], true))
                                            {{ $c['old'] !== null ? '£'.number_format((float) $c['old'], 2) : '—' }}
                                        @elseif ($field === 'lifts_people')
                                            {{ $c['old'] ? 'Yes' : 'No' }}
                                        @elseif ($field === 'resources_links')
                                            <span class="text-xs text-gray-500">{{ count($c['old'] ?? []) }} link(s)</span>
                                        @else
                                            {{ $c['old'] ?? '—' }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-900 font-medium">
                                        @if (! $c['will_change'])
                                            <span class="text-amber-700">{{ $c['summary'] }}</span>
                                        @elseif ($field === 'inspection_price')
                                            £{{ number_format((float) $c['new'], 2) }}
                                        @elseif ($field === 'lifts_people')
                                            {{ $c['new'] ? 'Yes' : 'No' }}
                                        @elseif ($field === 'resources_links')
                                            <span class="text-xs text-gray-700">{{ count($c['new'] ?? []) }} link(s)</span>
                                            <span class="block text-xs text-gray-500 mt-0.5">{{ $c['summary'] }}</span>
                                        @else
                                            {{ $c['new'] }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        @if ($c['will_change'])
                                            <span class="px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-700">Change</span>
                                        @else
                                            <span class="px-2 py-0.5 text-xs rounded-full bg-amber-100 text-amber-700">Skip</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <form method="POST" action="{{ route('kit-types.bulk-edit.apply') }}"
                  class="bg-white shadow-sm rounded-lg p-6">
                @csrf

                {{-- Re-post all original inputs so apply re-validates with the same data --}}
                @foreach ($request->validated()['kit_type_ids'] as $id)
                    <input type="hidden" name="kit_type_ids[]" value="{{ $id }}">
                @endforeach
                <input type="hidden" name="action" value="{{ $action }}">
                @if ($request->input('value') !== null)
                    <input type="hidden" name="value" value="{{ $request->input('value') }}">
                @endif
                @if ($request->input('link_name'))
                    <input type="hidden" name="link_name" value="{{ $request->input('link_name') }}">
                @endif
                @if ($request->input('link_url'))
                    <input type="hidden" name="link_url" value="{{ $request->input('link_url') }}">
                @endif

                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3">
                    <a href="{{ route('kit-types.index') }}" class="text-sm text-gray-600 hover:text-gray-900 text-center">Cancel</a>
                    <div class="flex gap-3">
                        <button type="button" onclick="history.back()"
                                class="px-4 py-2 rounded-xl border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                            ← Back to edit
                        </button>
                        <x-primary-button>
                            Apply to {{ $changingCount }} {{ Str::plural('type', $changingCount) }}
                        </x-primary-button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
