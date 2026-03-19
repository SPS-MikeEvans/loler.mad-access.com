<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            New Invoice — {{ $client->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('error'))
                <div class="px-4 py-3 bg-red-100 text-red-800 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Date range form --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Generate Invoice</h3>

                    <form method="POST" action="{{ route('clients.invoices.store', $client) }}" class="space-y-4">
                        @csrf

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="period_from" :value="__('Period From')" />
                                <x-text-input id="period_from" name="period_from" type="date" class="mt-1 block w-full"
                                    :value="old('period_from')" required />
                                <x-input-error :messages="$errors->get('period_from')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="period_to" :value="__('Period To')" />
                                <x-text-input id="period_to" name="period_to" type="date" class="mt-1 block w-full"
                                    :value="old('period_to')" required />
                                <x-input-error :messages="$errors->get('period_to')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="notes" :value="__('Notes (optional)')" />
                            <textarea id="notes" name="notes" rows="3"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-brand-red focus:ring-brand-red text-sm"
                                placeholder="Payment terms, references…">{{ old('notes') }}</textarea>
                            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>Generate Invoice</x-primary-button>
                            <a href="{{ route('clients.show', $client) }}">
                                <x-secondary-button type="button">Cancel</x-secondary-button>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Uninvoiced inspections preview --}}
            @if ($uninvoicedInspections->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-base font-medium text-gray-900 mb-3">
                            All Uninvoiced Inspections ({{ $uninvoicedInspections->count() }})
                        </h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Equipment</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Asset Tag</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Result</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($uninvoicedInspections as $inspection)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-2 text-gray-600">{{ $inspection->inspection_date->format('d M Y') }}</td>
                                            <td class="px-4 py-2 font-medium text-gray-900">{{ $inspection->kitItem->kitType->name }}</td>
                                            <td class="px-4 py-2 text-gray-600">{{ $inspection->kitItem->asset_tag ?? $inspection->kitItem->serial_no ?? '—' }}</td>
                                            <td class="px-4 py-2">
                                                <span class="px-2 py-0.5 text-xs rounded-full {{ $inspection->overall_status === 'pass' ? 'bg-green-100 text-green-800' : ($inspection->overall_status === 'fail' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                                    {{ ucfirst($inspection->overall_status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2 text-right text-gray-900">
                                                {{ $inspection->cost ? '£' . number_format($inspection->cost, 2) : '—' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="border-t-2 border-gray-300 bg-gray-50">
                                        <td colspan="4" class="px-4 py-2 text-right font-semibold text-gray-700">Total (all uninvoiced)</td>
                                        <td class="px-4 py-2 text-right font-bold text-gray-900">£{{ number_format($subtotal, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <p class="text-gray-500 italic">No uninvoiced completed inspections for this client.</p>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
