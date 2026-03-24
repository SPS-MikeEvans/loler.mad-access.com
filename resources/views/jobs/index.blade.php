<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Jobs') }}
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-7xl mx-auto mobile-shell">

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

            <div class="mobile-card overflow-hidden sm:rounded-lg">
                <div class="mobile-card-body">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-6">
                        <h3 class="text-lg font-medium text-gray-900">All Jobs</h3>
                        <a href="{{ route('jobs.create') }}" class="w-full sm:w-auto">
                            <x-primary-button class="w-full justify-center sm:w-auto">New Job</x-primary-button>
                        </a>
                    </div>

                    {{-- Filters --}}
                    <form method="GET" action="{{ route('jobs.index') }}" class="flex flex-wrap gap-3 mb-6">
                        <select name="status" class="border-gray-300 rounded-md text-sm">
                            <option value="">All statuses</option>
                            @foreach (['draft', 'open', 'in_progress', 'complete', 'returned'] as $s)
                                <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                            @endforeach
                        </select>
                        <select name="client_id" class="border-gray-300 rounded-md text-sm">
                            <option value="">All clients</option>
                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}" @selected(request('client_id') == $client->id)>{{ $client->name }}</option>
                            @endforeach
                        </select>
                        <x-primary-button type="submit">Filter</x-primary-button>
                        @if (request('status') || request('client_id'))
                            <a href="{{ route('jobs.index') }}" class="text-sm text-gray-500 self-center hover:underline">Clear</a>
                        @endif
                    </form>

                    @if ($jobs->isEmpty())
                        <p class="text-gray-500">No jobs found.</p>
                    @else
                        {{-- Mobile cards --}}
                        <div class="space-y-3 sm:hidden">
                            @foreach ($jobs as $job)
                                @php
                                    $statusColors = [
                                        'draft'       => 'bg-gray-100 text-gray-700',
                                        'open'        => 'bg-blue-100 text-blue-700',
                                        'in_progress' => 'bg-yellow-100 text-yellow-700',
                                        'complete'    => 'bg-green-100 text-green-700',
                                        'returned'    => 'bg-slate-100 text-slate-600',
                                    ];
                                @endphp
                                <div class="mobile-list-card">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <a href="{{ route('jobs.show', $job) }}" class="block text-base font-semibold text-slate-900 hover:text-brand-red">
                                                {{ $job->job_number }}
                                            </a>
                                            <p class="mt-1 text-sm text-slate-600">{{ $job->client->name }}</p>
                                        </div>
                                        <span class="mobile-chip {{ $statusColors[$job->status] ?? '' }} shrink-0">
                                            {{ ucfirst(str_replace('_', ' ', $job->status)) }}
                                        </span>
                                    </div>
                                    <div class="mt-3 mobile-action-group">
                                        <a href="{{ route('jobs.show', $job) }}" class="mobile-action-link">Open</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Desktop table --}}
                        <div class="hidden sm:block overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job #</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Drop Off</th>
                                        <th class="px-6 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($jobs as $job)
                                        @php
                                            $pill = [
                                                'draft'       => 'bg-gray-100 text-gray-700',
                                                'open'        => 'bg-blue-100 text-blue-700',
                                                'in_progress' => 'bg-yellow-100 text-yellow-700',
                                                'complete'    => 'bg-green-100 text-green-700',
                                                'returned'    => 'bg-slate-100 text-slate-600',
                                            ];
                                        @endphp
                                        <tr>
                                            <td class="px-6 py-4 font-medium text-gray-900">
                                                <a href="{{ route('jobs.show', $job) }}" class="hover:underline">{{ $job->job_number }}</a>
                                            </td>
                                            <td class="px-6 py-4 text-gray-700">{{ $job->client->name }}</td>
                                            <td class="px-6 py-4">
                                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $pill[$job->status] ?? '' }}">
                                                    {{ ucfirst(str_replace('_', ' ', $job->status)) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-gray-600">{{ $job->created_at->format('d M Y') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-gray-600">{{ $job->drop_off_at?->format('d M Y') ?? '—' }}</td>
                                            <td class="px-6 py-4 text-right">
                                                <a href="{{ route('jobs.show', $job) }}" class="text-brand-navy hover:text-brand-red text-sm font-medium">Open →</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $jobs->links() }}
                        </div>
                    @endif
                </div>
            </div>

            @if (auth()->user()->isAdmin())
                @foreach ($jobs as $job)
                    <x-confirmed-action-modal
                        :name="$deleteConfirmations[$job->id]->modalName"
                        title="Delete Job"
                        :message="'This permanently deletes job '.$job->job_number.'. Type the phrase below to continue.'"
                        :phrase="$deleteConfirmations[$job->id]->phrase"
                        :action="route('jobs.destroy', $job)"
                        method="DELETE"
                        submit-label="Delete Job"
                        :password-confirm="true"
                    />
                @endforeach
            @endif

        </div>
    </div>
</x-app-layout>
