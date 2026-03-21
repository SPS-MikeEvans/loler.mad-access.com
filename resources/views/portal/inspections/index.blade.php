<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Inspection History — {{ $kitItem->kitType->name }}
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-4xl mx-auto mobile-shell">
            <div class="mobile-card overflow-hidden sm:rounded-lg">
                <div class="mobile-card-body">

                    @if ($inspections->isEmpty())
                        <p class="text-gray-500 italic">No completed inspections on record for this item.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Result</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Next Due</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Notes</th>
                                        <th class="px-4 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @foreach ($inspections as $inspection)
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
                                            <td class="px-4 py-3 text-gray-600 max-w-xs">
                                                {{ Str::limit($inspection->report_notes, 80) ?: '—' }}
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

                    <div class="mt-6">
                        <a href="{{ route('portal.kit.show', $kitItem) }}" class="text-sm text-brand-navy hover:text-brand-red">← Back to item</a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
