<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Expense') }} — {{ $expense->supplier }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="px-4 py-3 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase">Date</dt>
                        <dd class="mt-1">{{ $expense->expense_date->format('d F Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase">Supplier</dt>
                        <dd class="mt-1 font-medium">{{ $expense->supplier }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase">Category</dt>
                        <dd class="mt-1">{{ $expense->category->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase">Amount</dt>
                        <dd class="mt-1 text-lg font-bold text-brand-navy">£{{ number_format($expense->amount, 2) }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-medium text-gray-500 uppercase">Notes</dt>
                        <dd class="mt-1 text-gray-700">{{ $expense->notes ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase">Receipt</dt>
                        <dd class="mt-1">
                            @if ($expense->receipt_path)
                                <a href="{{ route('accounting.expenses.receipt', $expense) }}" class="text-brand-navy underline">Download</a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase">Reconciled</dt>
                        <dd class="mt-1">{{ $expense->reconciled_at?->format('d M Y') ?? 'Not yet' }}</dd>
                    </div>
                </dl>

                <div class="mt-6 flex gap-2 flex-wrap">
                    <a href="{{ route('accounting.expenses.edit', $expense) }}" class="px-4 py-2 bg-brand-navy text-white text-sm font-semibold rounded-md hover:bg-brand-navy/80">Edit</a>
                    <button type="button"
                            class="px-4 py-2 border border-red-300 text-red-600 text-sm font-semibold rounded-md hover:bg-red-50"
                            x-data
                            x-on:click="$dispatch('open-modal', '{{ $deleteConfirmation->modalName }}')">
                        Delete
                    </button>
                    <a href="{{ route('accounting.expenses.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Back to list</a>
                </div>
            </div>
        </div>
    </div>

    <x-confirmed-action-modal
        :name="$deleteConfirmation->modalName"
        title="Delete Expense"
        :message="'Delete expense from '.$expense->supplier.' (£'.number_format($expense->amount, 2).'). The receipt file will be removed. Type the phrase below to continue.'"
        :phrase="$deleteConfirmation->phrase"
        :action="route('accounting.expenses.destroy', $expense)"
        method="DELETE"
        submit-label="Delete Expense"
        :password-confirm="true"
    />
</x-app-layout>
