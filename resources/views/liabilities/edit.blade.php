<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Company Liabilities &amp; Insurance
        </h2>
    </x-slot>

    @php
        $insuranceRows = old('insurances', $liability->insurances ?? []);
        if (empty($insuranceRows)) {
            $insuranceRows = [[
                'name' => '',
                'insurer' => '',
                'policy_number' => '',
                'expiry' => '',
                'limit' => '',
                'certificate_path' => null,
            ]];
        }

        $insuranceRowsForJs = collect($insuranceRows)
            ->map(fn ($row) => [
                'name' => $row['name'] ?? '',
                'insurer' => $row['insurer'] ?? '',
                'policy_number' => $row['policy_number'] ?? '',
                'expiry' => $row['expiry'] ?? '',
                'limit' => $row['limit'] ?? '',
                'certificate_path' => $row['certificate_path'] ?? ($row['existing_certificate_path'] ?? null),
                'remove_certificate' => !empty($row['remove_certificate']),
            ])
            ->values()
            ->all();
        $certificateBaseUrl = rtrim(Storage::url(''), '/') . '/';
    @endphp

    <div class="py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if ($errors->any())
                        <div class="mb-6 px-4 py-3 bg-red-100 text-red-800 rounded-lg">
                            <strong>Please correct the errors below.</strong>
                            <ul class="mt-1 list-disc list-inside text-sm">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('liabilities.update') }}" enctype="multipart/form-data" class="space-y-8">
                        @csrf
                        @method('PUT')

                        <div>
                            <x-input-label for="terms_and_conditions" :value="__('Terms & Conditions')" />
                            <textarea
                                id="terms_and_conditions"
                                name="terms_and_conditions"
                                rows="14"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-brand-red focus:ring-brand-red text-sm"
                                placeholder="Enter the company terms and conditions shown on the public liabilities page..."
                            >{{ old('terms_and_conditions', $liability->terms_and_conditions) }}</textarea>
                            <p class="mt-2 text-sm text-gray-500">Rendered as plain text with preserved line breaks on the public page.</p>
                            <x-input-error :messages="$errors->get('terms_and_conditions')" class="mt-2" />
                        </div>

                        <div
                            x-data="{
                                rows: @js($insuranceRowsForJs),
                                addRow() {
                                    this.rows.push({
                                        name: '',
                                        insurer: '',
                                        policy_number: '',
                                        expiry: '',
                                        limit: '',
                                        certificate_path: null,
                                        remove_certificate: false,
                                    });
                                }
                            }"
                            class="space-y-4"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">Insurance Policies</h3>
                                    <p class="text-sm text-gray-500">Add structured insurance details and optionally attach the current certificate PDF for each policy.</p>
                                </div>
                                <button
                                    type="button"
                                    x-on:click="addRow()"
                                    class="inline-flex items-center px-4 py-2 bg-brand-navy text-white text-sm font-medium rounded-md hover:bg-brand-navy/90"
                                >
                                    + Add Insurance
                                </button>
                            </div>

                            <div class="space-y-4">
                                <template x-for="(row, index) in rows" :key="index">
                                    <div class="border border-gray-200 rounded-lg p-5 space-y-4">
                                        <div class="flex items-center justify-between">
                                            <h4 class="text-sm font-semibold text-gray-900" x-text="`Policy ${index + 1}`"></h4>
                                            <button
                                                type="button"
                                                x-on:click="rows.splice(index, 1)"
                                                class="text-sm text-red-600 hover:text-red-700"
                                            >
                                                Remove Policy
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <x-input-label ::for="`insurance-name-${index}`" :value="__('Policy Name')" />
                                                <input
                                                    :id="`insurance-name-${index}`"
                                                    :name="`insurances[${index}][name]`"
                                                    x-model="row.name"
                                                    type="text"
                                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-brand-red focus:ring-brand-red text-sm"
                                                    placeholder="Public Liability"
                                                >
                                            </div>
                                            <div>
                                                <x-input-label ::for="`insurance-insurer-${index}`" :value="__('Insurer')" />
                                                <input
                                                    :id="`insurance-insurer-${index}`"
                                                    :name="`insurances[${index}][insurer]`"
                                                    x-model="row.insurer"
                                                    type="text"
                                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-brand-red focus:ring-brand-red text-sm"
                                                    placeholder="Aviva"
                                                >
                                            </div>
                                            <div>
                                                <x-input-label ::for="`insurance-policy-${index}`" :value="__('Policy Number')" />
                                                <input
                                                    :id="`insurance-policy-${index}`"
                                                    :name="`insurances[${index}][policy_number]`"
                                                    x-model="row.policy_number"
                                                    type="text"
                                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-brand-red focus:ring-brand-red text-sm"
                                                    placeholder="PL-123456"
                                                >
                                            </div>
                                            <div>
                                                <x-input-label ::for="`insurance-expiry-${index}`" :value="__('Expiry Date')" />
                                                <input
                                                    :id="`insurance-expiry-${index}`"
                                                    :name="`insurances[${index}][expiry]`"
                                                    x-model="row.expiry"
                                                    type="date"
                                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-brand-red focus:ring-brand-red text-sm"
                                                >
                                            </div>
                                            <div>
                                                <x-input-label ::for="`insurance-limit-${index}`" :value="__('Cover Limit (£)')" />
                                                <input
                                                    :id="`insurance-limit-${index}`"
                                                    :name="`insurances[${index}][limit]`"
                                                    x-model="row.limit"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-brand-red focus:ring-brand-red text-sm"
                                                    placeholder="10000000"
                                                >
                                            </div>
                                            <div>
                                                <x-input-label ::for="`insurance-certificate-${index}`" :value="__('Certificate PDF')" />
                                                <input
                                                    :id="`insurance-certificate-${index}`"
                                                    :name="`insurances[${index}][certificate]`"
                                                    type="file"
                                                    accept=".pdf"
                                                    class="mt-1 block w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:bg-brand-navy/10 file:text-brand-navy hover:file:bg-brand-navy/20"
                                                >
                                                <input
                                                    type="hidden"
                                                    :name="`insurances[${index}][existing_certificate_path]`"
                                                    :value="row.certificate_path ?? ''"
                                                >
                                                <input
                                                    type="hidden"
                                                    :name="`insurances[${index}][remove_certificate]`"
                                                    :value="row.remove_certificate ? '1' : '0'"
                                                >
                                            </div>
                                        </div>

                                        <template x-if="row.certificate_path && !row.remove_certificate">
                                            <div class="flex items-center gap-3 text-sm">
                                                <a
                                                    :href="`{{ $certificateBaseUrl }}${row.certificate_path}`"
                                                    target="_blank"
                                                    rel="noopener"
                                                    class="text-brand-navy hover:text-brand-red"
                                                >
                                                    View current certificate
                                                </a>
                                                <span class="text-gray-300">|</span>
                                                <button
                                                    type="button"
                                                    x-on:click="row.remove_certificate = true"
                                                    class="text-red-600 hover:text-red-700"
                                                >
                                                    Remove certificate
                                                </button>
                                            </div>
                                        </template>

                                        <template x-if="row.certificate_path && row.remove_certificate">
                                            <div class="flex items-center gap-3 text-sm">
                                                <span class="text-red-600">Certificate will be removed on save.</span>
                                                <button
                                                    type="button"
                                                    x-on:click="row.remove_certificate = false"
                                                    class="text-gray-600 hover:text-gray-800"
                                                >
                                                    Undo
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <x-primary-button>Save Liabilities</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
