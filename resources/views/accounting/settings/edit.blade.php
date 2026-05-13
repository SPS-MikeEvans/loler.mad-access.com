<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Business Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="px-4 py-3 bg-green-100 text-green-800 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Banking details for invoices</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            These details print on every issued invoice as payment instructions. Leave any field blank to hide it on the PDF.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('accounting.settings.update') }}" class="space-y-5">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="bank_name" value="Bank name" />
                                <x-text-input id="bank_name" name="bank_name" type="text" class="mt-1 block w-full"
                                              :value="old('bank_name', $settings->bank_name)" placeholder="e.g. Tide" />
                                <x-input-error :messages="$errors->get('bank_name')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="account_holder" value="Account holder" />
                                <x-text-input id="account_holder" name="account_holder" type="text" class="mt-1 block w-full"
                                              :value="old('account_holder', $settings->account_holder)" />
                                <x-input-error :messages="$errors->get('account_holder')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="sort_code" value="Sort code" />
                                <x-text-input id="sort_code" name="sort_code" type="text" class="mt-1 block w-full"
                                              :value="old('sort_code', $settings->sort_code)" placeholder="12-34-56" />
                                <x-input-error :messages="$errors->get('sort_code')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="account_number" value="Account number" />
                                <x-text-input id="account_number" name="account_number" type="text" class="mt-1 block w-full"
                                              :value="old('account_number', $settings->account_number)" placeholder="8 digits" />
                                <x-input-error :messages="$errors->get('account_number')" class="mt-2" />
                            </div>
                            <div class="sm:col-span-2">
                                <x-input-label for="iban" value="IBAN (optional)" />
                                <x-text-input id="iban" name="iban" type="text" class="mt-1 block w-full"
                                              :value="old('iban', $settings->iban)" />
                                <x-input-error :messages="$errors->get('iban')" class="mt-2" />
                            </div>
                            <div class="sm:col-span-2">
                                <x-input-label for="reference_instructions" value="Reference instructions" />
                                <textarea id="reference_instructions" name="reference_instructions" rows="2"
                                          class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('reference_instructions', $settings->reference_instructions) }}</textarea>
                                <p class="mt-1 text-xs text-gray-500">Shown to the client beside the bank details on the invoice PDF.</p>
                                <x-input-error :messages="$errors->get('reference_instructions')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="payment_terms_days" value="Payment terms (days)" />
                                <x-text-input id="payment_terms_days" name="payment_terms_days" type="number" min="0" max="120"
                                              class="mt-1 block w-full"
                                              :value="old('payment_terms_days', $settings->payment_terms_days)" />
                                <p class="mt-1 text-xs text-gray-500">Used to default the due date on new invoices.</p>
                                <x-input-error :messages="$errors->get('payment_terms_days')" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <x-primary-button type="submit">Save settings</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
