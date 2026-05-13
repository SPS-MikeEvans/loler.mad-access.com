<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Bank Connections') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="px-4 py-3 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="px-4 py-3 bg-red-100 text-red-800 rounded-lg">{{ session('error') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between gap-3 flex-wrap">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Open Banking Connections</h3>
                            <p class="text-sm text-gray-600 mt-1">
                                We use the GoCardless Bank Account Data API (Open Banking) to pull TIDE transactions for reconciliation.
                                Consent lasts up to {{ config('banking.gocardless.agreement_days', 90) }} days.
                            </p>
                        </div>
                        <form method="POST" action="{{ route('accounting.bank-connections.connect') }}">
                            @csrf
                            <input type="hidden" name="institution_id" value="{{ config('banking.gocardless.default_institution_id') }}">
                            <x-primary-button type="submit">Connect TIDE</x-primary-button>
                        </form>
                    </div>

                    @if ($connections->isEmpty())
                        <p class="text-gray-500 italic">No bank connections yet.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Institution</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Linked</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Expires</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Last synced</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($connections as $connection)
                                        <tr>
                                            <td class="px-4 py-3 font-medium text-gray-900">
                                                {{ $connection->institution_name ?? $connection->institution_id }}
                                                @if ($connection->needsRelink())
                                                    <span class="ml-2 px-2 py-0.5 text-xs rounded-full bg-red-100 text-red-800">Re-link required</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3">
                                                @php
                                                    $badge = match ($connection->status) {
                                                        'linked' => 'bg-green-100 text-green-800',
                                                        'pending' => 'bg-blue-100 text-blue-800',
                                                        'expired' => 'bg-red-100 text-red-800',
                                                        'revoked' => 'bg-gray-200 text-gray-700',
                                                        default => 'bg-gray-100 text-gray-700',
                                                    };
                                                @endphp
                                                <span class="px-2 py-0.5 text-xs rounded-full {{ $badge }}">{{ ucfirst($connection->status) }}</span>
                                            </td>
                                            <td class="px-4 py-3 text-gray-600">{{ $connection->linked_at?->format('d M Y') ?? '—' }}</td>
                                            <td class="px-4 py-3 text-gray-600">{{ $connection->expires_at?->format('d M Y') ?? '—' }}</td>
                                            <td class="px-4 py-3 text-gray-600">{{ $connection->last_synced_at?->diffForHumans() ?? '—' }}</td>
                                            <td class="px-4 py-3 text-right">
                                                <div class="inline-flex items-center gap-2">
                                                    @if ($connection->needsRelink())
                                                        <form method="POST" action="{{ route('accounting.bank-connections.connect') }}" class="inline">
                                                            @csrf
                                                            <input type="hidden" name="institution_id" value="{{ $connection->institution_id }}">
                                                            <button type="submit" class="text-sm text-brand-navy hover:text-brand-red">Re-connect</button>
                                                        </form>
                                                    @elseif ($connection->status === 'linked')
                                                        <form method="POST" action="{{ route('accounting.bank-connections.sync', $connection) }}" class="inline">
                                                            @csrf
                                                            <button type="submit" class="text-sm text-brand-navy hover:text-brand-red">Sync now</button>
                                                        </form>
                                                    @endif
                                                    <span class="text-gray-300">|</span>
                                                    <button type="button"
                                                            class="text-sm text-red-600 hover:text-red-800"
                                                            x-data
                                                            x-on:click="$dispatch('open-modal', '{{ $deleteConfirmations[$connection->id]->modalName }}')">
                                                        Remove
                                                    </button>
                                                </div>
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

    @foreach ($connections as $connection)
        <x-confirmed-action-modal
            :name="$deleteConfirmations[$connection->id]->modalName"
            title="Remove Bank Connection"
            :message="'Removing this connection deletes the linked transactions and any reconciliations against them. Type the phrase below to continue.'"
            :phrase="$deleteConfirmations[$connection->id]->phrase"
            :action="route('accounting.bank-connections.destroy', $connection)"
            method="DELETE"
            submit-label="Remove Connection"
            :password-confirm="true"
        />
    @endforeach
</x-app-layout>
