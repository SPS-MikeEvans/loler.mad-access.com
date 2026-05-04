<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Kit Groups - {{ $client->name }}
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
                    <div class="grid grid-cols-1 gap-3 mb-4 sm:flex sm:items-center sm:justify-between">
                        <h3 class="min-w-0 break-words pr-0 text-lg font-medium text-gray-900 sm:pr-4">Kit Groups - {{ $client->name }}</h3>
                        <a href="{{ route('clients.kit-groups.create', $client) }}" class="w-full sm:w-auto">
                            <x-primary-button class="w-full justify-center sm:w-auto">New Group</x-primary-button>
                        </a>
                    </div>

                    <form method="GET" action="{{ route('clients.kit-groups.index', $client) }}" class="mb-4">
                        <input
                            type="search"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Search groups by name or description..."
                            class="block w-full border-gray-300 rounded-xl shadow-sm text-sm focus:border-brand-red focus:ring-brand-red sm:w-80" />
                    </form>

                    @if ($kitGroups->isEmpty())
                        <p class="text-gray-500 italic">
                            No kit groups yet. <a href="{{ route('clients.kit-groups.create', $client) }}" class="text-brand-navy underline">Create the first group.</a>
                        </p>
                    @else
                        <div class="block space-y-3 sm:hidden">
                            @foreach ($kitGroups as $group)
                                <div class="mobile-list-card">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <a href="{{ route('clients.kit-groups.show', [$client, $group]) }}" class="text-base font-semibold text-slate-900 hover:text-brand-red break-words">
                                                {{ $group->name }}
                                            </a>
                                            @if ($group->description)
                                                <p class="text-sm text-slate-500 mt-1 break-words">{{ $group->description }}</p>
                                            @endif
                                        </div>
                                        <span class="mobile-chip bg-slate-100 text-slate-700 shrink-0">
                                            {{ $group->kit_items_count }} {{ Str::plural('item', $group->kit_items_count) }}
                                        </span>
                                    </div>
                                    <div class="mt-3 mobile-action-group">
                                        <a href="{{ route('clients.kit-groups.show', [$client, $group]) }}" class="mobile-action-link">View</a>
                                        <a href="{{ route('clients.kit-groups.edit', [$client, $group]) }}" class="mobile-action-link">Edit</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="hidden sm:block overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                                        <th class="px-6 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($kitGroups as $group)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 font-medium text-gray-900">
                                                <a href="{{ route('clients.kit-groups.show', [$client, $group]) }}" class="hover:text-brand-red">
                                                    {{ $group->name }}
                                                </a>
                                            </td>
                                            <td class="px-6 py-4 text-gray-600">{{ $group->description ?? '-' }}</td>
                                            <td class="px-6 py-4 text-gray-600">{{ $group->kit_items_count }}</td>
                                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                                <a href="{{ route('clients.kit-groups.show', [$client, $group]) }}" class="text-sm text-brand-navy hover:text-brand-red font-medium mr-4">View</a>
                                                <a href="{{ route('clients.kit-groups.edit', [$client, $group]) }}" class="text-sm text-brand-navy hover:text-brand-red font-medium">Edit</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $kitGroups->links() }}
                        </div>
                    @endif

                    <div class="mt-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-4">
                        <a href="{{ route('clients.kit-items.index', $client) }}" class="text-sm text-brand-navy hover:text-brand-red">View Kit List</a>
                        <a href="{{ route('clients.show', $client) }}" class="text-sm text-brand-navy hover:text-brand-red">Back to {{ $client->name }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
