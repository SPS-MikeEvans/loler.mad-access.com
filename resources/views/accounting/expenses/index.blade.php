<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Expenses') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="px-4 py-3 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between gap-4 flex-wrap">
                        <h3 class="text-lg font-medium text-gray-900">All Expenses</h3>
                        <a href="{{ route('accounting.expenses.create') }}" class="inline-flex items-center px-4 py-2 bg-brand-navy text-white text-sm font-semibold rounded-md hover:bg-brand-navy/80">
                            Add expense
                        </a>
                    </div>

                    <form method="GET" class="flex flex-wrap items-end gap-3 text-sm">
                        <div>
                            <x-input-label for="category" value="Category" />
                            <select id="category" name="category" class="mt-1 border-gray-300 rounded-md text-sm">
                                <option value="">All</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}" @selected(request('category') == $cat->id)>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <label class="inline-flex items-center gap-2 mt-5">
                            <input type="checkbox" name="unreconciled" value="1" @checked(request()->boolean('unreconciled'))
                                   class="rounded border-gray-300">
                            <span>Unreconciled only</span>
                        </label>
                        <x-secondary-button type="submit">Filter</x-secondary-button>
                    </form>

                    @if ($expenses->isEmpty())
                        <p class="text-gray-500 italic">No expenses match your filter.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Supplier</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Reconciled</th>
                                        <th class="px-4 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($expenses as $expense)
                                        <tr>
                                            <td class="px-4 py-3 text-gray-600">{{ $expense->expense_date->format('d M Y') }}</td>
                                            <td class="px-4 py-3 font-medium text-gray-900">{{ $expense->supplier }}</td>
                                            <td class="px-4 py-3 text-gray-600">{{ $expense->category->name ?? '—' }}</td>
                                            <td class="px-4 py-3 text-right text-gray-900">£{{ number_format($expense->amount, 2) }}</td>
                                            <td class="px-4 py-3">
                                                @if ($expense->reconciled_at)
                                                    <span class="px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-800">Yes</span>
                                                @else
                                                    <span class="px-2 py-0.5 text-xs rounded-full bg-gray-200 text-gray-700">No</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-right text-sm">
                                                <a href="{{ route('accounting.expenses.show', $expense) }}" class="text-brand-navy hover:text-brand-red">View</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="mt-4">{{ $expenses->links() }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
