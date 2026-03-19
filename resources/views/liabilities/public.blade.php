<x-guest-layout>
    <div class="py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <div>
                <h1 class="text-3xl font-bold text-brand-navy">Liabilities &amp; Insurance</h1>
                <p class="mt-2 text-sm text-gray-500">Terms, conditions, and current insurance certificates for MaD-ACCESS.</p>
            </div>

            <div class="bg-white shadow-sm rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Terms &amp; Conditions</h2>
                @if ($liability->terms_and_conditions)
                    <div class="prose max-w-none text-sm text-gray-700">
                        {!! nl2br(e($liability->terms_and_conditions)) !!}
                    </div>
                @else
                    <p class="text-sm text-gray-500 italic">Terms and conditions have not been published yet.</p>
                @endif
            </div>

            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="flex items-center justify-between gap-4 mb-4">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Current Insurance Cover</h2>
                        <p class="text-sm text-gray-500">Current policies and certificate documents.</p>
                    </div>
                </div>

                @if (! empty($liability->insurances))
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Policy</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Insurer</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Policy Number</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Expiry</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Limit</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Certificate</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($liability->insurances as $policy)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-900">{{ $policy['name'] ?: '—' }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $policy['insurer'] ?: '—' }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $policy['policy_number'] ?: '—' }}</td>
                                        <td class="px-4 py-3 text-gray-700">
                                            {{ !empty($policy['expiry']) ? \Illuminate\Support\Carbon::parse($policy['expiry'])->format('d M Y') : '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-700">
                                            {{ isset($policy['limit']) && $policy['limit'] !== null ? '£' . number_format((float) $policy['limit'], 2) : '—' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            @if (!empty($policy['certificate_path']))
                                                <a
                                                    href="{{ Storage::url($policy['certificate_path']) }}"
                                                    target="_blank"
                                                    rel="noopener"
                                                    class="text-brand-navy hover:text-brand-red"
                                                >
                                                    View PDF
                                                </a>
                                            @else
                                                <span class="text-gray-400">Not uploaded</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-500 italic">Insurance policies have not been added yet.</p>
                @endif
            </div>
        </div>
    </div>
</x-guest-layout>
