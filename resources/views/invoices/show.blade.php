<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Invoice {{ $invoice->invoice_number }} — {{ $client->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="px-4 py-3 bg-green-100 text-green-800 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Invoice summary card --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
                        <div>
                            <h3 class="text-2xl font-bold text-brand-navy">{{ $invoice->invoice_number }}</h3>
                            <p class="text-sm text-gray-500 mt-1">Issued {{ $invoice->issued_date->format('d F Y') }}</p>
                            <p class="text-sm text-gray-600 mt-1">
                                Period: {{ $invoice->period_from->format('d M Y') }} – {{ $invoice->period_to->format('d M Y') }}
                            </p>
                        </div>
                        <div class="flex gap-2 flex-wrap">
                            @can('view-reports')
                                <a href="{{ route('clients.invoices.pdf', [$client, $invoice]) }}"
                                   class="inline-flex items-center gap-2 px-4 py-2 bg-brand-navy text-white text-sm font-semibold rounded-md hover:bg-brand-navy/80 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h4a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                                    </svg>
                                    Download PDF
                                </a>
                            @endcan
                            @if(auth()->user()->role === 'admin')
                                <form method="POST" action="{{ route('clients.invoices.destroy', [$client, $invoice]) }}"
                                      x-data="{ confirmed: false }"
                                      x-on:submit.prevent="confirmed ? $el.submit() : (confirmed = true)">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center gap-2 px-4 py-2 border border-red-300 text-red-600 text-sm font-semibold rounded-md hover:bg-red-50 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        <span x-text="confirmed ? 'Tap again to confirm' : 'Delete Invoice'"></span>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    {{-- Bill to --}}
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg text-sm">
                        <p class="font-semibold text-gray-700 mb-1">Bill To</p>
                        <p class="text-gray-900 font-medium">{{ $client->name }}</p>
                        @if ($client->address)
                            <p class="text-gray-600">{{ $client->address }}</p>
                        @endif
                        @if ($client->contact_email)
                            <p class="text-gray-600">{{ $client->contact_email }}</p>
                        @endif
                    </div>

                    {{-- Inspections table --}}
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Equipment</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase hidden sm:table-cell">Asset Tag</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase hidden sm:table-cell">Inspector</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Result</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($invoice->inspections->sortBy('inspection_date') as $inspection)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-gray-600">{{ $inspection->inspection_date->format('d M Y') }}</td>
                                        <td class="px-4 py-3 font-medium text-gray-900">{{ $inspection->kitItem->kitType->name }}</td>
                                        <td class="px-4 py-3 text-gray-600 hidden sm:table-cell">{{ $inspection->kitItem->asset_tag ?? $inspection->kitItem->serial_no ?? '—' }}</td>
                                        <td class="px-4 py-3 text-gray-600 hidden sm:table-cell">{{ $inspection->inspector->name ?? '—' }}</td>
                                        <td class="px-4 py-3">
                                            <span class="px-2 py-0.5 text-xs rounded-full {{ $inspection->overall_status === 'pass' ? 'bg-green-100 text-green-800' : ($inspection->overall_status === 'fail' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                                {{ ucfirst($inspection->overall_status) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right text-gray-900">
                                            {{ $inspection->cost ? '£' . number_format($inspection->cost, 2) : '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="border-t-2 border-gray-300 bg-gray-50">
                                    <td colspan="5" class="px-4 py-3 text-right font-bold text-gray-800">Total</td>
                                    <td class="px-4 py-3 text-right font-bold text-xl text-brand-navy">£{{ number_format($invoice->total_amount, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    @if ($invoice->notes)
                        <div class="mt-6 p-4 bg-amber-50 border border-amber-200 rounded-lg text-sm text-gray-700">
                            <p class="font-semibold mb-1">Notes</p>
                            {!! nl2br(e($invoice->notes)) !!}
                        </div>
                    @endif
                </div>
            </div>

            <div>
                <a href="{{ route('clients.show', $client) }}" class="text-sm text-brand-navy hover:text-brand-red">← Back to {{ $client->name }}</a>
            </div>

        </div>
    </div>
</x-app-layout>
