<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ $kitGroup->name }} - {{ $client->name }}
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-4xl mx-auto mobile-shell space-y-6">
            @if (session('success'))
                <div class="px-4 py-3 bg-green-100 text-green-800 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mobile-card sm:rounded-lg">
                <div class="mobile-card-body">
                    <div class="flex items-start justify-between gap-4 mb-4">
                        <div class="min-w-0">
                            <h3 class="text-lg font-semibold text-gray-900 break-words">{{ $kitGroup->name }}</h3>
                            @if ($kitGroup->description)
                                <p class="text-sm text-gray-600 mt-1 break-words">{{ $kitGroup->description }}</p>
                            @endif
                        </div>
                        <a href="{{ route('clients.kit-groups.edit', [$client, $kitGroup]) }}">
                            <x-secondary-button>Edit</x-secondary-button>
                        </a>
                    </div>

                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Items in this group ({{ $kitGroup->kitItems->count() }})</h4>

                    @if ($kitGroup->kitItems->isEmpty())
                        <p class="text-sm text-gray-500 italic">No items in this group yet.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Asset / Serial</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-4 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($kitGroup->kitItems as $item)
                                        <tr>
                                            <td class="px-4 py-3 text-gray-900 font-medium">{{ $item->typeName() }}</td>
                                            <td class="px-4 py-3 text-gray-600">{{ $item->asset_tag ?? $item->serial_no ?? '-' }}</td>
                                            <td class="px-4 py-3 text-gray-600">{{ ucfirst(str_replace('_', ' ', $item->status)) }}</td>
                                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                                <a href="{{ route('clients.kit-items.show', [$client, $item]) }}" class="text-brand-navy hover:text-brand-red font-medium">View</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="px-1">
                <a href="{{ route('clients.kit-groups.index', $client) }}" class="text-sm text-brand-navy hover:text-brand-red">Back to Kit Groups</a>
            </div>
        </div>
    </div>
</x-app-layout>
