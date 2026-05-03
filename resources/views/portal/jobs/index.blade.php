<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('My Jobs') }}
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-4xl mx-auto mobile-shell">

            @if (session('success'))
                <div class="mb-4 px-4 py-3 bg-green-100 text-green-800 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mobile-card sm:rounded-lg">
                <div class="mobile-card-body">
                    <div class="grid grid-cols-1 gap-3 mb-5 sm:flex sm:items-center sm:justify-between">
                        <h3 class="text-lg font-medium text-gray-900">Jobs &amp; Inspection Manifests</h3>
                        <a href="{{ route('portal.jobs.create') }}" class="w-full sm:w-auto">
                            <x-primary-button class="w-full justify-center sm:w-auto">Request Inspection</x-primary-button>
                        </a>
                    </div>

                    @if ($jobs->isEmpty())
                        <p class="text-gray-500">No jobs on record yet. <a href="{{ route('portal.jobs.create') }}" class="text-brand-navy underline">Request your first inspection.</a></p>
                    @else
                        <div class="space-y-3">
                            @foreach ($jobs as $job)
                                @php
                                    $pill = [
                                        'draft'       => 'bg-gray-100 text-gray-600',
                                        'open'        => 'bg-blue-100 text-blue-700',
                                        'in_progress' => 'bg-yellow-100 text-yellow-700',
                                        'complete'    => 'bg-green-100 text-green-700',
                                        'returned'    => 'bg-slate-100 text-slate-600',
                                    ];
                                    $label = [
                                        'draft'       => 'Awaiting Review',
                                        'open'        => 'Items Received',
                                        'in_progress' => 'In Inspection',
                                        'complete'    => 'Ready for Collection',
                                        'returned'    => 'Returned',
                                    ];
                                @endphp
                                <div class="mobile-list-card">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <a href="{{ route('portal.jobs.show', $job) }}" class="text-base font-semibold text-slate-900 hover:text-brand-red">
                                                {{ $job->job_number }}
                                            </a>
                                            <p class="text-sm text-slate-500 mt-0.5">{{ $job->created_at->format('d M Y') }}</p>
                                        </div>
                                        <span class="mobile-chip {{ $pill[$job->status] ?? '' }} shrink-0">
                                            {{ $label[$job->status] ?? $job->status }}
                                        </span>
                                    </div>
                                    @if ($job->status === 'complete')
                                        <div class="mt-2 text-sm text-green-700 font-medium">
                                            ✓ All inspections complete — please collect your items.
                                        </div>
                                    @endif
                                    <div class="mt-3 mobile-action-group">
                                        <a href="{{ route('portal.jobs.show', $job) }}" class="mobile-action-link">Track Job →</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4">
                            {{ $jobs->links() }}
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
