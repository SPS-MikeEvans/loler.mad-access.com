<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            User Management
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 px-4 py-3 bg-green-100 text-green-800 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 px-4 py-3 bg-red-100 text-red-800 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">All Users</h3>
                        <a href="{{ route('users.create') }}">
                            <x-primary-button>Add User</x-primary-button>
                        </a>
                    </div>

                    @if ($users->isEmpty())
                        <p class="text-gray-500 italic">No users found.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="hidden sm:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                        <th class="hidden md:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qualifications</th>
                                        <th class="hidden md:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cert Expiry</th>
                                        <th class="px-6 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($users as $user)
                                        @php
                                            $roleBadge = match($user->role) {
                                                'admin'         => 'bg-purple-100 text-purple-800',
                                                'inspector'     => 'bg-blue-100 text-blue-800',
                                                'client_viewer' => 'bg-gray-100 text-gray-700',
                                                default         => 'bg-gray-100 text-gray-600',
                                            };
                                            $expiryClass = $user->qualification_expiry?->isPast()
                                                ? 'text-red-600 font-semibold'
                                                : ($user->qualification_expiry?->diffInDays(now()) <= 30 && $user->qualification_expiry?->isFuture()
                                                    ? 'text-amber-600 font-semibold'
                                                    : 'text-gray-600');
                                        @endphp
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                                                {{ $user->name }}
                                                @if ($user->competent_person_flag)
                                                    <span class="ml-1 text-xs text-green-700 bg-green-50 rounded px-1">CP</span>
                                                @endif
                                            </td>
                                            <td class="hidden sm:table-cell px-6 py-4 whitespace-nowrap text-gray-600 text-sm">{{ $user->email }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs rounded-full {{ $roleBadge }}">
                                                    {{ ucfirst(str_replace('_', ' ', $user->role)) }}
                                                </span>
                                            </td>
                                            <td class="hidden md:table-cell px-6 py-4 text-sm text-gray-600 max-w-xs truncate">
                                                {{ $user->qualifications ? \Str::limit($user->qualifications, 60) : '—' }}
                                            </td>
                                            <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap text-sm {{ $expiryClass }}">
                                                {{ $user->qualification_expiry?->format('d M Y') ?? '—' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm space-x-4">
                                                <a href="{{ route('users.edit', $user) }}"
                                                   class="text-amber-600 hover:text-amber-900">Edit</a>
                                                @if (!$user->is(auth()->user()))
                                                    <form method="POST"
                                                          action="{{ route('users.destroy', $user) }}"
                                                          class="inline"
                                                          x-data
                                                          x-on:submit.prevent="
                                                              $el.querySelector('button').textContent !== 'Confirm?'
                                                                  ? ($el.querySelector('button').textContent = 'Confirm?')
                                                                  : $el.submit()
                                                          ">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                                    </form>
                                                @endif
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
