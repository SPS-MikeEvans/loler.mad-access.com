<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Bank Transactions') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="px-4 py-3 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 space-y-4">
                    <form method="GET" class="flex flex-wrap items-end gap-3 text-sm">
                        <div>
                            <x-input-label for="from" value="From" />
                            <x-text-input id="from" name="from" type="date" :value="request('from')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="to" value="To" />
                            <x-text-input id="to" name="to" type="date" :value="request('to')" class="mt-1" />
                        </div>
                        <label class="inline-flex items-center gap-2 mt-5">
                            <input type="checkbox" name="unreconciled" value="1" @checked(request()->boolean('unreconciled'))
                                   class="rounded border-gray-300">
                            <span>Unreconciled only</span>
                        </label>
                        <x-secondary-button type="submit">Filter</x-secondary-button>
                    </form>

                    @if ($transactions->isEmpty())
                        <p class="text-gray-500 italic">No transactions to display.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Counterparty</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($transactions as $tx)
                                        <tr>
                                            <td class="px-4 py-3 text-gray-600">{{ $tx->booking_date->format('d M Y') }}</td>
                                            <td class="px-4 py-3 font-medium text-gray-900">{{ $tx->counterparty_name ?? '—' }}</td>
                                            <td class="px-4 py-3 text-gray-600 truncate max-w-xs">{{ $tx->description ?? '' }}</td>
                                            <td class="px-4 py-3 text-right font-medium {{ $tx->isCredit() ? 'text-green-700' : 'text-red-700' }}">
                                                £{{ number_format(abs($tx->amount), 2) }}{{ $tx->isDebit() ? ' Dr' : ' Cr' }}
                                            </td>
                                            <td class="px-4 py-3">
                                                @if ($tx->reconciled_at)
                                                    <span class="px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-800">Reconciled</span>
                                                @else
                                                    <span class="px-2 py-0.5 text-xs rounded-full bg-gray-200 text-gray-700">Open</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="mt-4">{{ $transactions->links() }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
