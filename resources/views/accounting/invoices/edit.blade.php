<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Edit Invoice') }} {{ $invoice->invoice_number }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm text-gray-500">{{ $invoice->client->name }}</p>
                        <p class="text-lg font-medium text-gray-900">{{ $invoice->invoice_number }}</p>
                    </div>
                    <span class="px-2 py-0.5 text-xs font-bold uppercase tracking-wider rounded-full {{ $invoice->status->badgeClasses() }}">
                        {{ $invoice->status->label() }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-4 p-4 bg-gray-50 rounded-lg text-sm">
                    <div>
                        <p class="text-gray-500">Subtotal</p>
                        <p class="font-medium text-gray-900">£{{ number_format($invoice->subtotal, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Current total</p>
                        <p class="font-medium text-gray-900">£{{ number_format($invoice->total_amount, 2) }}</p>
                    </div>
                    <p class="col-span-2 text-xs text-gray-500">
                        The total is recalculated from the subtotal when you change the discount. Amounts themselves cannot be edited here.
                    </p>
                </div>

                <form method="POST" action="{{ route('accounting.invoices.update', $invoice) }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="issued_date" value="Issued date" />
                            <x-text-input id="issued_date" type="date" name="issued_date" class="mt-1 block w-full"
                                          :value="old('issued_date', $invoice->issued_date?->format('Y-m-d'))" required />
                            <x-input-error :messages="$errors->get('issued_date')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="due_date" value="Due date" />
                            <x-text-input id="due_date" type="date" name="due_date" class="mt-1 block w-full"
                                          :value="old('due_date', $invoice->due_date?->format('Y-m-d'))" required />
                            <x-input-error :messages="$errors->get('due_date')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="period_from" value="Period from" />
                            <x-text-input id="period_from" type="date" name="period_from" class="mt-1 block w-full"
                                          :value="old('period_from', $invoice->period_from?->format('Y-m-d'))" required />
                            <x-input-error :messages="$errors->get('period_from')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="period_to" value="Period to" />
                            <x-text-input id="period_to" type="date" name="period_to" class="mt-1 block w-full"
                                          :value="old('period_to', $invoice->period_to?->format('Y-m-d'))" required />
                            <x-input-error :messages="$errors->get('period_to')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="discount_percent" value="Discount % (optional)" />
                        <x-text-input id="discount_percent" type="number" name="discount_percent" step="0.01" min="0.01" max="99.99"
                                      class="mt-1 block w-full sm:w-40"
                                      :value="old('discount_percent', $invoice->discount_percent)" />
                        <x-input-error :messages="$errors->get('discount_percent')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="notes" value="Notes" />
                        <textarea id="notes" name="notes" rows="3"
                                  class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('notes', $invoice->notes) }}</textarea>
                        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('accounting.invoices.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Cancel</a>
                        <x-primary-button type="submit">Save changes</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
