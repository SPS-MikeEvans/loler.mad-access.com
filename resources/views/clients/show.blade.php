<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ $client->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 px-4 py-3 bg-green-100 text-green-800 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 space-y-4">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Company Name</p>
                        <p class="mt-1 text-gray-900">{{ $client->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Contact Person</p>
                        <p class="mt-1 text-gray-900">{{ $client->contact_name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Address</p>
                        <p class="mt-1 text-gray-900">{{ $client->address }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Contact Email</p>
                        <p class="mt-1 text-gray-900">{{ $client->contact_email }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</p>
                        <p class="mt-1 text-gray-900">{{ $client->phone }}</p>
                    </div>
                    @if ($client->notes)
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</p>
                        <p class="mt-1 text-gray-900 whitespace-pre-line">{{ $client->notes }}</p>
                    </div>
                    @endif
                </div>

                <div class="px-6 py-4 bg-gray-50 flex items-center gap-4 flex-wrap">
                    <a href="{{ route('clients.edit', $client) }}">
                        <x-primary-button>Edit</x-primary-button>
                    </a>
                    <form action="{{ route('clients.destroy', $client) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <x-danger-button
                            onclick="return confirm('Delete {{ addslashes($client->name) }}? This cannot be undone.')">
                            Delete
                        </x-danger-button>
                    </form>
                    <a href="{{ route('clients.index') }}" class="text-sm text-gray-600 hover:text-gray-900">← Back to Clients</a>
                </div>

                @can('view-reports')
                    <div class="px-6 py-4 border-t border-gray-100" x-data="{ open: false }">
                        <button @click="open = !open" class="text-sm font-medium text-brand-navy hover:text-brand-red">
                            Generate Inspection Report ↓
                        </button>
                        <div x-show="open" x-cloak class="mt-3 flex items-end gap-3">
                            <form method="GET" action="{{ route('clients.reports.inspections', $client) }}" class="flex items-end gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Year</label>
                                    <select name="year" class="border-gray-300 rounded-md shadow-sm text-sm focus:border-brand-red focus:ring-brand-red">
                                        @for ($y = now()->year; $y >= now()->year - 3; $y--)
                                            <option value="{{ $y }}" {{ $y == now()->year ? 'selected' : '' }}>{{ $y }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Month</label>
                                    <select name="month" class="border-gray-300 rounded-md shadow-sm text-sm focus:border-brand-red focus:ring-brand-red">
                                        @foreach (range(1, 12) as $m)
                                            <option value="{{ $m }}" {{ $m == now()->month ? 'selected' : '' }}>
                                                {{ \Carbon\Carbon::createFromDate(null, $m, 1)->format('F') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <x-secondary-button type="submit">Download PDF</x-secondary-button>
                            </form>
                        </div>
                    </div>
                @endcan

                @if(auth()->user()->role === 'admin')
                    <div class="px-6 py-4 border-t border-gray-100">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-medium text-gray-900">Invoices</h4>
                            <a href="{{ route('clients.invoices.create', $client) }}"
                               class="text-sm font-semibold text-brand-red hover:text-red-700">
                                + Create Invoice
                            </a>
                        </div>
                        @if ($client->invoices->isEmpty())
                            <p class="text-sm text-gray-500 italic">No invoices yet.</p>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Invoice #</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase hidden sm:table-cell">Period</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                            <th class="px-3 py-2"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach ($client->invoices as $invoice)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-3 py-2 font-medium text-brand-navy">
                                                    <a href="{{ route('clients.invoices.show', [$client, $invoice]) }}" class="hover:text-brand-red">
                                                        {{ $invoice->invoice_number }}
                                                    </a>
                                                </td>
                                                <td class="px-3 py-2 text-gray-600 hidden sm:table-cell">
                                                    {{ $invoice->period_from->format('d M Y') }} – {{ $invoice->period_to->format('d M Y') }}
                                                </td>
                                                <td class="px-3 py-2 text-right font-semibold text-gray-900">
                                                    £{{ number_format($invoice->total_amount, 2) }}
                                                </td>
                                                <td class="px-3 py-2 text-right">
                                                    @can('view-reports')
                                                        <a href="{{ route('clients.invoices.pdf', [$client, $invoice]) }}"
                                                           class="text-xs text-brand-navy hover:text-brand-red font-medium">PDF</a>
                                                    @endcan
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
