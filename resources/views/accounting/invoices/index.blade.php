<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Invoices') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="px-4 py-3 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="px-4 py-3 bg-red-100 text-red-800 rounded-lg">{{ session('error') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 space-y-4">
                    <h3 class="text-lg font-medium text-gray-900">All Invoices</h3>

                    <form method="GET" class="flex flex-wrap items-end gap-3 text-sm">
                        <div>
                            <x-input-label for="status" value="Status" />
                            <select id="status" name="status" class="mt-1 border-gray-300 rounded-md text-sm">
                                <option value="">All</option>
                                <option value="unpaid" @selected(request('status') === 'unpaid')>Unpaid</option>
                                @foreach (\App\Enums\InvoiceStatus::cases() as $statusOption)
                                    <option value="{{ $statusOption->value }}" @selected(request('status') === $statusOption->value)>{{ $statusOption->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="client" value="Client" />
                            <select id="client" name="client" class="mt-1 border-gray-300 rounded-md text-sm">
                                <option value="">All</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}" @selected(request('client') == $client->id)>{{ $client->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="search" value="Search" />
                            <input id="search" type="text" name="search" value="{{ request('search') }}" placeholder="Invoice # or client"
                                   class="mt-1 border-gray-300 rounded-md text-sm">
                        </div>
                        <div>
                            <x-input-label for="from" value="Issued from" />
                            <input id="from" type="date" name="from" value="{{ request('from') }}" class="mt-1 border-gray-300 rounded-md text-sm">
                        </div>
                        <div>
                            <x-input-label for="to" value="Issued to" />
                            <input id="to" type="date" name="to" value="{{ request('to') }}" class="mt-1 border-gray-300 rounded-md text-sm">
                        </div>
                        <x-secondary-button type="submit">Filter</x-secondary-button>
                        @if (request()->hasAny(['status', 'client', 'search', 'from', 'to']))
                            <a href="{{ route('accounting.invoices.index') }}" class="px-2 py-2 text-sm text-gray-500 hover:text-gray-900">Clear</a>
                        @endif
                    </form>

                    @if ($invoices->isEmpty())
                        <p class="text-gray-500 italic">No invoices match your filter.</p>
                    @else
                        <div class="overflow-x-auto xl:overflow-visible">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Invoice #</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Issued</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Due</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Auto Emails</th>
                                        <th class="px-4 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($invoices as $invoice)
                                        <tr>
                                            <td class="px-4 py-3 font-medium text-gray-900">
                                                @if (! $invoice->client->trashed())
                                                    <a href="{{ route('clients.invoices.show', [$invoice->client, $invoice]) }}" class="text-brand-navy hover:text-brand-red">{{ $invoice->invoice_number }}</a>
                                                @else
                                                    {{ $invoice->invoice_number }}
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-gray-600">
                                                {{ $invoice->client->name }}
                                                @if ($invoice->client->trashed())
                                                    <span class="text-xs text-gray-400">(deleted)</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $invoice->issued_date?->format('d M Y') ?? '—' }}</td>
                                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $invoice->due_date?->format('d M Y') ?? '—' }}</td>
                                            <td class="px-4 py-3">
                                                <span class="px-2 py-0.5 text-xs font-bold uppercase tracking-wider rounded-full {{ $invoice->status->badgeClasses() }}">
                                                    {{ $invoice->status === \App\Enums\InvoiceStatus::Cancelled ? 'Void' : $invoice->status->label() }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-right text-gray-900 whitespace-nowrap">£{{ number_format($invoice->total_amount, 2) }}</td>
                                            <td class="px-4 py-3">
                                                @if ($invoice->chase_emails_paused_at)
                                                    <span class="px-2 py-0.5 text-xs rounded-full bg-yellow-100 text-yellow-800">Paused</span>
                                                @else
                                                    <span class="px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-800">On</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                @if (! $invoice->status->isTerminal())
                                                    <x-dropdown align="right" width="56">
                                                        <x-slot name="trigger">
                                                            <button type="button" class="p-1 rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100" aria-label="Invoice actions">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                                    <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                                                </svg>
                                                            </button>
                                                        </x-slot>
                                                        <x-slot name="content">
                                                            <x-dropdown-link :href="route('accounting.invoices.edit', $invoice)">
                                                                Edit
                                                            </x-dropdown-link>
                                                            @if (! $invoice->client->trashed())
                                                                <form method="POST" action="{{ route('clients.invoices.send', [$invoice->client, $invoice]) }}">
                                                                    @csrf
                                                                    <button type="submit" class="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out">
                                                                        Email Invoice
                                                                    </button>
                                                                </form>
                                                            @endif
                                                            @if ($invoice->chase_emails_paused_at)
                                                                <form method="POST" action="{{ route('accounting.invoices.resume-chases', $invoice) }}">
                                                                    @csrf
                                                                    <button type="submit" class="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out">
                                                                        Turn On Automated Emails
                                                                    </button>
                                                                </form>
                                                            @else
                                                                <form method="POST" action="{{ route('accounting.invoices.pause-chases', $invoice) }}">
                                                                    @csrf
                                                                    <button type="submit" class="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out">
                                                                        Turn Off Automated Emails
                                                                    </button>
                                                                </form>
                                                            @endif
                                                            @isset ($cancelConfirmations[$invoice->id])
                                                                <button type="button"
                                                                    class="block w-full px-4 py-2 text-start text-sm leading-5 text-red-600 hover:bg-red-50 focus:outline-none focus:bg-red-50 transition duration-150 ease-in-out"
                                                                    x-data
                                                                    x-on:click="$dispatch('open-modal', '{{ $cancelConfirmations[$invoice->id]->modalName }}')">
                                                                    Void
                                                                </button>
                                                            @endisset
                                                        </x-slot>
                                                    </x-dropdown>
                                                @else
                                                    <span class="text-gray-300">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">{{ $invoices->links() }}</div>

                        @foreach ($invoices as $invoice)
                            @isset ($cancelConfirmations[$invoice->id])
                                <x-confirmed-action-modal
                                    :name="$cancelConfirmations[$invoice->id]->modalName"
                                    title="Void Invoice"
                                    :message="'This voids invoice '.$invoice->invoice_number.' for '.$invoice->client->name.'. It cannot be un-voided. Type the phrase below to continue.'"
                                    :phrase="$cancelConfirmations[$invoice->id]->phrase"
                                    :action="route('clients.invoices.cancel', [$invoice->client, $invoice])"
                                    method="POST"
                                    submit-label="Void Invoice"
                                    :password-confirm="true"
                                />
                            @endisset
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
