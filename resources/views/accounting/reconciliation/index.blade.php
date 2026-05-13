<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Reconciliation') }}
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

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                {{-- Bank Transactions --}}
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 space-y-3">
                        <h3 class="text-lg font-medium text-gray-900">Unreconciled Bank Transactions</h3>
                        @if ($unreconciledTransactions->isEmpty())
                            <p class="text-gray-500 italic">Nothing to reconcile — pull the latest bank feed or add expenses.</p>
                        @else
                            <ul class="space-y-3">
                                @foreach ($unreconciledTransactions as $tx)
                                    <li class="border border-gray-200 rounded-lg p-3 text-sm">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <div class="font-medium text-gray-900">{{ $tx->counterparty_name ?? '—' }}</div>
                                                <div class="text-xs text-gray-500">{{ $tx->booking_date->format('d M Y') }} · {{ $tx->bankConnection?->institution_name ?? '—' }}</div>
                                                @if ($tx->description)
                                                    <div class="text-xs text-gray-600 mt-1">{{ \Illuminate\Support\Str::limit($tx->description, 70) }}</div>
                                                @endif
                                            </div>
                                            <div class="text-right">
                                                <div class="font-bold {{ $tx->isCredit() ? 'text-green-700' : 'text-red-700' }}">
                                                    £{{ number_format(abs($tx->amount), 2) }}{{ $tx->isDebit() ? ' Dr' : ' Cr' }}
                                                </div>
                                            </div>
                                        </div>

                                        @php $suggestions = $suggestionsByTx[$tx->id] ?? collect(); @endphp
                                        @if ($suggestions->isNotEmpty())
                                            <div class="mt-3 border-t pt-3">
                                                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Suggested matches</div>
                                                <ul class="space-y-1">
                                                    @foreach ($suggestions as $suggestion)
                                                        <li>
                                                            <form method="POST" action="{{ route('accounting.reconciliation.match') }}" class="flex items-center justify-between gap-2">
                                                                @csrf
                                                                <input type="hidden" name="bank_transaction_id" value="{{ $tx->id }}">
                                                                @if ($suggestion instanceof \App\Models\Invoice)
                                                                    <input type="hidden" name="matchable_type" value="invoice">
                                                                    <input type="hidden" name="matchable_id" value="{{ $suggestion->id }}">
                                                                    <span class="text-sm">📄 {{ $suggestion->invoice_number }} — {{ $suggestion->client->name }} — £{{ number_format($suggestion->total_amount, 2) }}</span>
                                                                @else
                                                                    <input type="hidden" name="matchable_type" value="expense">
                                                                    <input type="hidden" name="matchable_id" value="{{ $suggestion->id }}">
                                                                    <span class="text-sm">💳 {{ $suggestion->supplier }} — £{{ number_format($suggestion->amount, 2) }}</span>
                                                                @endif
                                                                <button type="submit" class="px-3 py-1 text-xs bg-brand-navy text-white rounded hover:bg-brand-navy/80">Match</button>
                                                            </form>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>

                {{-- Manual match panel --}}
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 space-y-4">
                        <h3 class="text-lg font-medium text-gray-900">Manual Match</h3>
                        <p class="text-xs text-gray-500">Pick a bank transaction and target, optionally specify a partial amount.</p>

                        <form method="POST" action="{{ route('accounting.reconciliation.match') }}" class="space-y-3">
                            @csrf
                            <div>
                                <x-input-label for="bank_transaction_id" value="Bank transaction" />
                                <select id="bank_transaction_id" name="bank_transaction_id" class="mt-1 block w-full border-gray-300 rounded-md text-sm">
                                    @foreach ($unreconciledTransactions as $tx)
                                        <option value="{{ $tx->id }}">{{ $tx->booking_date->format('d M Y') }} · {{ $tx->counterparty_name ?? '—' }} · £{{ number_format(abs($tx->amount), 2) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="matchable_type" value="Target" />
                                <select id="matchable_type" name="matchable_type" class="mt-1 block w-full border-gray-300 rounded-md text-sm" x-data x-on:change="$dispatch('match-target-changed', $event.target.value)">
                                    <option value="invoice">Invoice</option>
                                    <option value="expense">Expense</option>
                                </select>
                            </div>

                            <div>
                                <x-input-label for="matchable_id" value="Invoice or expense" />
                                <select id="matchable_id" name="matchable_id" class="mt-1 block w-full border-gray-300 rounded-md text-sm">
                                    <optgroup label="Invoices">
                                        @foreach ($unpaidInvoices as $inv)
                                            <option value="{{ $inv->id }}" data-type="invoice">{{ $inv->invoice_number }} — {{ $inv->client->name }} — outstanding £{{ number_format($inv->outstandingAmount(), 2) }}</option>
                                        @endforeach
                                    </optgroup>
                                    <optgroup label="Expenses">
                                        @foreach ($unreconciledExpenses as $exp)
                                            <option value="{{ $exp->id }}" data-type="expense">{{ $exp->supplier }} — £{{ number_format($exp->amount, 2) }}</option>
                                        @endforeach
                                    </optgroup>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Tip: change "Target" above to Invoice/Expense, then pick the corresponding row.</p>
                            </div>

                            <div>
                                <x-input-label for="matched_amount" value="Matched amount (optional, defaults to full)" />
                                <x-text-input id="matched_amount" name="matched_amount" type="number" step="0.01" min="0.01" class="mt-1 block w-full" />
                            </div>

                            <div>
                                <x-input-label for="notes" value="Notes" />
                                <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full border-gray-300 rounded-md"></textarea>
                            </div>

                            <div class="flex justify-end">
                                <x-primary-button type="submit">Record match</x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Recent reconciliations --}}
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 space-y-3">
                    <h3 class="text-lg font-medium text-gray-900">Recent Reconciliations</h3>
                    @if ($recentReconciliations->isEmpty())
                        <p class="text-gray-500 italic">No reconciliations yet.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">When</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Bank tx</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Target</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                                        <th class="px-4 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($recentReconciliations as $rec)
                                        <tr>
                                            <td class="px-4 py-3 text-gray-600">{{ $rec->created_at->format('d M Y H:i') }}</td>
                                            <td class="px-4 py-3 text-gray-700">{{ $rec->bankTransaction?->counterparty_name ?? '#'.$rec->bank_transaction_id }}</td>
                                            <td class="px-4 py-3 text-gray-700">
                                                @if ($rec->matchable_type === \App\Models\Reconciliation::TYPE_INVOICE)
                                                    Invoice {{ optional($rec->matchable)->invoice_number ?? '#'.$rec->matchable_id }}
                                                @else
                                                    Expense {{ optional($rec->matchable)->supplier ?? '#'.$rec->matchable_id }}
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-right">£{{ number_format($rec->matched_amount, 2) }}</td>
                                            <td class="px-4 py-3 text-right">
                                                <button type="button" class="text-sm text-red-600 hover:text-red-800"
                                                        x-data x-on:click="$dispatch('open-modal', '{{ $unmatchConfirmations[$rec->id]->modalName }}')">
                                                    Unmatch
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @foreach ($recentReconciliations as $rec)
        <x-confirmed-action-modal
            :name="$unmatchConfirmations[$rec->id]->modalName"
            title="Unmatch Reconciliation"
            :message="'This reverses the match. If it was the last reconciliation for an invoice that was Paid, the invoice will revert to Sent. Type the phrase below to continue.'"
            :phrase="$unmatchConfirmations[$rec->id]->phrase"
            :action="route('accounting.reconciliation.destroy', $rec)"
            method="DELETE"
            submit-label="Unmatch"
            :password-confirm="true"
        />
    @endforeach
</x-app-layout>
