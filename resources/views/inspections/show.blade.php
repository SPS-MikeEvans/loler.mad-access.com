<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Inspection — {{ $kitItem->kitType->name }}
            @if ($kitItem->asset_tag)
                <span class="text-gray-500 font-normal text-lg">({{ $kitItem->asset_tag }})</span>
            @endif
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Summary card --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Inspection Date</dt>
                        <dd class="mt-1 text-gray-900">{{ $inspection->inspection_date->format('d M Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Inspector</dt>
                        <dd class="mt-1 text-gray-900">{{ $inspection->inspector->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Overall Result</dt>
                        <dd class="mt-1">
                            @php
                                $badge = match($inspection->overall_status) {
                                    'pass' => 'bg-green-100 text-green-800',
                                    'conditional' => 'bg-yellow-100 text-yellow-800',
                                    'fail' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-600',
                                };
                                $label = match($inspection->overall_status) {
                                    'pass' => 'Pass',
                                    'conditional' => 'Conditional',
                                    'fail' => 'Fail',
                                    default => ucfirst($inspection->overall_status),
                                };
                            @endphp
                            <span class="px-2 py-1 text-xs rounded-full font-medium {{ $badge }}">{{ $label }}</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Next Due</dt>
                        <dd class="mt-1 text-gray-900">{{ $inspection->next_due_date->format('d M Y') }}</dd>
                    </div>
                    @can('edit-inspection-cost')
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Cost</dt>
                            <dd class="mt-1 text-gray-900">{{ $inspection->cost !== null ? '£' . number_format((float) $inspection->cost, 2) : '—' }}</dd>
                        </div>
                    @endcan
                </dl>

                @can('edit-inspection-cost')
                    <form method="POST" action="{{ route('inspections.update-cost', $inspection) }}" class="mt-4 pt-4 border-t border-gray-100 flex items-end gap-3">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label for="cost_edit" class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Update Cost (£)</label>
                            <input type="number" id="cost_edit" name="cost" step="0.01" min="0" max="99999"
                                value="{{ $inspection->cost }}"
                                class="block border-gray-300 rounded-md shadow-sm text-sm focus:border-brand-red focus:ring-brand-red w-36"
                                placeholder="0.00">
                        </div>
                        <x-primary-button type="submit">Save Cost</x-primary-button>
                    </form>
                @endcan
            </div>

            {{-- Checklist --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="font-semibold text-gray-900">Checklist</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-2/5">Check</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-28">Result</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-28">Photo</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($inspection->checks as $check)
                                @php
                                    $rowClass = match($check->status) {
                                        'fail' => 'bg-red-50',
                                        'n/a' => 'bg-gray-50',
                                        default => '',
                                    };
                                    $statusBadge = match($check->status) {
                                        'pass' => 'bg-green-100 text-green-800',
                                        'fail' => 'bg-red-100 text-red-800',
                                        'n/a' => 'bg-gray-100 text-gray-600',
                                        default => 'bg-gray-100 text-gray-600',
                                    };
                                    $statusLabel = match($check->status) {
                                        'pass' => 'Pass',
                                        'fail' => 'Fail',
                                        'n/a' => 'N/A',
                                        default => ucfirst($check->status),
                                    };
                                @endphp
                                <tr class="{{ $rowClass }}">
                                    <td class="px-4 py-3">
                                        <span class="inline-block text-xs font-medium text-brand-navy bg-brand-blue/20 rounded px-1.5 py-0.5 mr-1">{{ $check->check_category }}</span>
                                        <span class="text-sm text-gray-900">{{ $check->check_text }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 text-xs rounded-full font-medium {{ $statusBadge }}">{{ $statusLabel }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $check->notes ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        @if ($check->photos->isNotEmpty())
                                            <div class="flex flex-wrap gap-1">
                                                @foreach ($check->photos as $photo)
                                                    <a href="{{ Storage::url($photo->path) }}" target="_blank" rel="noopener">
                                                        <img src="{{ Storage::url($photo->path) }}" alt="Check photo"
                                                             class="h-12 w-12 object-cover rounded border border-gray-200 hover:opacity-80">
                                                    </a>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Report notes + signature --}}
            @if ($inspection->report_notes || $inspection->digital_sig_path)
                <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-5">
                    @if ($inspection->report_notes)
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-2">Report Notes</h3>
                            <p class="text-sm text-gray-700 whitespace-pre-line">{{ $inspection->report_notes }}</p>
                        </div>
                    @endif
                    @if ($inspection->digital_sig_path)
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-2">Inspector Signature</h3>
                            <img src="{{ Storage::url($inspection->digital_sig_path) }}" alt="Inspector signature"
                                 class="max-h-24 border border-gray-200 rounded">
                        </div>
                    @endif
                </div>
            @endif

            <div class="flex items-center gap-6">
                <a href="{{ route('clients.kit-items.show', [$client, $kitItem]) }}" class="text-sm text-brand-navy hover:text-brand-red">
                    ← Back to {{ $kitItem->kitType->name }}
                </a>
                @can('view-reports')
                    <a href="{{ route('inspections.pdf', $inspection) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-brand-navy text-white text-sm font-medium rounded-md hover:bg-brand-red">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Download LOLER Thorough Examination PDF
                    </a>
                @endcan
            </div>

        </div>
    </div>
</x-app-layout>
