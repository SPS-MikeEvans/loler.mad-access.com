<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Clients') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 px-4 py-3 bg-green-100 text-green-800 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-medium text-gray-900">All Clients</h3>
                        <a href="{{ route('clients.create') }}">
                            <x-primary-button>Add Client</x-primary-button>
                        </a>
                    </div>

                    @if ($clients->isEmpty())
                        <p class="text-gray-500">No clients yet. Add your first one above.</p>
                    @else
                        <div class="overflow-x-auto">
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
