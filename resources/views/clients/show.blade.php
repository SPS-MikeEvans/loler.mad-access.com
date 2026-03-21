<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ $client->name }}
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-4xl mx-auto mobile-shell mobile-stack">

            @if (session('success'))
                <div class="mb-4 px-4 py-3 bg-green-100 text-green-800 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mobile-card overflow-hidden sm:rounded-lg">
                <div class="mobile-card-body space-y-4">
                    <div class="sm:hidden">
                        <p class="mobile-kicker">Client Record</p>
                        <h3 class="mobile-section-title mt-2">{{ $client->name }}</h3>
                    </div>

                    <dl class="mobile-meta-grid">
                        <div class="mobile-meta-item">
                            <dt class="mobile-meta-label">Company Name</dt>
                            <dd class="mobile-meta-value">{{ $client->name }}</dd>
                        </div>
                        <div class="mobile-meta-item">
                            <dt class="mobile-meta-label">Contact Person</dt>
                            <dd class="mobile-meta-value">{{ $client->contact_name ?? '—' }}</dd>
                        </div>
                        <div class="mobile-meta-item sm:col-span-2">
                            <dt class="mobile-meta-label">Address</dt>
                            <dd class="mobile-meta-value">{{ $client->address }}</dd>
                        </div>
                        <div class="mobile-meta-item">
                            <dt class="mobile-meta-label">Contact Email</dt>
                            <dd class="mobile-meta-value">{{ $client->contact_email }}</dd>
                        </div>
                        <div class="mobile-meta-item">
                            <dt class="mobile-meta-label">Phone</dt>
                            <dd class="mobile-meta-value">{{ $client->phone }}</dd>
                        </div>
                        @if ($client->notes)
                            <div class="mobile-meta-item sm:col-span-2">
                                <dt class="mobile-meta-label">Notes</dt>
                                <dd class="mobile-meta-value whitespace-pre-line">{{ $client->notes }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                <div class="px-4 py-4 bg-gray-50 sm:px-6">
                    <div class="mobile-action-group">
                    <a href="{{ route('clients.kit-items.index', $client) }}" class="mobile-action-link">View Kit List</a>
                    <a href="{{ route('clients.edit', $client) }}" class="w-full sm:w-auto">
                        <x-primary-button class="w-full justify-center sm:w-auto">Edit</x-primary-button>
                    </a>
                    <form action="{{ route('clients.destroy', $client) }}" method="POST" class="w-full sm:w-auto">
                        @csrf
                        @method('DELETE')
                        <x-danger-button
                            class="w-full justify-center sm:w-auto"
                            onclick="return confirm('Delete {{ addslashes($client->name) }}? This cannot be undone.')">
                            Delete
                        </x-danger-button>
                    </form>
                    <a href="{{ route('clients.index') }}" class="mobile-action-link">Back to Clients</a>
                    </div>
                </div>

                @can('view-reports')
                    <div class="border-t border-gray-100" x-data="{ open: false }">
                        <button @click="open = !open" class="mobile-disclosure-button">
                            <div>
                                <p class="mobile-kicker">Reports</p>
                                <p class="mt-1 text-sm font-semibold text-brand-navy">Generate inspection report</p>
                            </div>
                            <svg class="h-5 w-5 text-slate-400 transition" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" x-cloak class="px-4 pb-4 sm:px-6">
                            <form method="GET" action="{{ route('clients.reports.inspections', $client) }}" class="grid grid-cols-1 gap-3 sm:flex sm:items-end">
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
                                <x-secondary-button type="submit" class="w-full justify-center sm:w-auto">Download PDF</x-secondary-button>
                            </form>
                        </div>
                    </div>
                @endcan

                @if(auth()->user()->role === 'admin')
                    <div class="px-4 py-4 border-t border-gray-100 sm:px-6">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-3">
                            <h4 class="text-sm font-medium text-gray-900">Invoices</h4>
                            <a href="{{ route('clients.invoices.create', $client) }}"
                               class="text-sm font-semibold text-brand-red hover:text-red-700">
                                + Create Invoice
                            </a>
                        </div>
                        @if ($client->invoices->isEmpty())
                            <p class="text-sm text-gray-500 italic">No invoices yet.</p>
                        @else
                            <div class="space-y-3 sm:hidden">
                                @foreach ($client->invoices as $invoice)
                                    <div class="mobile-list-card">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <a href="{{ route('clients.invoices.show', [$client, $invoice]) }}" class="text-base font-semibold text-brand-navy hover:text-brand-red">
                                                    {{ $invoice->invoice_number }}
                                                </a>
                                                <p class="mt-1 text-sm text-slate-600">{{ $invoice->period_from->format('d M Y') }} - {{ $invoice->period_to->format('d M Y') }}</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="mobile-meta-label">Total</p>
                                                <p class="text-base font-semibold text-slate-900">£{{ number_format($invoice->total_amount, 2) }}</p>
                                            </div>
                                        </div>
                                        @can('view-reports')
                                            <div class="mt-4 mobile-action-group">
                                                <a href="{{ route('clients.invoices.pdf', [$client, $invoice]) }}" class="mobile-action-link">Open PDF</a>
                                            </div>
                                        @endcan
                                    </div>
                                @endforeach
                            </div>

                            <div class="hidden sm:block overflow-x-auto">
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
