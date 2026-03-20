<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Clients') }}
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-7xl mx-auto mobile-shell">

            @if (session('success'))
                <div class="mb-4 px-4 py-3 bg-green-100 text-green-800 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mobile-card overflow-hidden sm:rounded-lg">
                <div class="mobile-card-body">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-6">
                        <h3 class="text-lg font-medium text-gray-900">All Clients</h3>
                        <a href="{{ route('clients.create') }}" class="w-full sm:w-auto">
                            <x-primary-button class="w-full justify-center sm:w-auto">Add Client</x-primary-button>
                        </a>
                    </div>

                    @if ($clients->isEmpty())
                        <p class="text-gray-500">No clients yet. Add your first one above.</p>
                    @else
                        <div class="space-y-3 sm:hidden">
                            @foreach ($clients as $client)
                                <div class="mobile-list-card">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <a href="{{ route('clients.show', $client) }}" class="block text-base font-semibold text-slate-900 hover:text-brand-red">{{ $client->name }}</a>
                                            <p class="mt-1 text-sm text-slate-600">{{ $client->contact_name ?? 'No contact name set' }}</p>
                                        </div>
                                        <a href="{{ route('clients.kit-items.index', $client) }}" class="mobile-chip bg-brand-blue/20 text-brand-navy shrink-0">Kit List</a>
                                    </div>

                                    <div class="mt-4 mobile-meta-grid">
                                        <div class="mobile-meta-item">
                                            <p class="mobile-meta-label">Phone</p>
                                            <p class="mobile-meta-value">{{ $client->phone ?: 'Not provided' }}</p>
                                        </div>
                                        <div class="mobile-meta-item">
                                            <p class="mobile-meta-label">Email</p>
                                            <p class="mobile-meta-value">{{ $client->contact_email ?: 'Not provided' }}</p>
                                        </div>
                                    </div>

                                    <div class="mt-4 mobile-action-group">
                                        <a href="{{ route('clients.show', $client) }}" class="mobile-action-link">Open Client</a>
                                        <a href="{{ route('clients.edit', $client) }}" class="mobile-action-link">Edit</a>
                                        <form action="{{ route('clients.destroy', $client) }}" method="POST" class="w-full sm:w-auto">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="mobile-action-link border-red-200 text-red-600 hover:border-red-300 hover:bg-red-50 w-full"
                                                onclick="return confirm('Delete {{ addslashes($client->name) }}?')">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="hidden sm:block overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="hidden sm:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                        <th class="hidden sm:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                        <th class="px-6 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($clients as $client)
                                        <tr>
                                            <td class="px-6 py-4 font-medium text-gray-900">
                                                <a href="{{ route('clients.show', $client) }}" class="hover:underline">{{ $client->name }}</a>
                                            </td>
                                            <td class="hidden sm:table-cell px-6 py-4 whitespace-nowrap text-gray-600">{{ $client->contact_name ?? '—' }}</td>
                                            <td class="hidden sm:table-cell px-6 py-4 whitespace-nowrap text-gray-600">{{ $client->phone }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                                <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-4 space-y-1 sm:space-y-0 items-end">
                                                    <a href="{{ route('clients.kit-items.index', $client) }}" class="text-brand-navy hover:text-brand-red">Kit List</a>
                                                    <a href="{{ route('clients.edit', $client) }}" class="text-brand-navy hover:text-brand-red">Edit</a>
                                                    <form action="{{ route('clients.destroy', $client) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="text-red-600 hover:text-red-900"
                                                            onclick="return confirm('Delete {{ addslashes($client->name) }}?')">
                                                            Delete
                                                        </button>
                                                    </form>
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
</x-app-layout>
