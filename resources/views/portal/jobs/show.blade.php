<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ $job->job_number }}
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-3xl mx-auto mobile-shell mobile-stack">

            @php
                $pill = [
                    'draft'       => 'bg-gray-100 text-gray-600',
                    'open'        => 'bg-blue-100 text-blue-700',
                    'in_progress' => 'bg-yellow-100 text-yellow-700',
                    'complete'    => 'bg-green-100 text-green-700',
                    'returned'    => 'bg-slate-100 text-slate-600',
                ];
                $label = [
                    'draft'       => 'Draft',
                    'open'        => 'Items Received',
                    'in_progress' => 'Inspections in Progress',
                    'complete'    => 'Ready for Collection',
                    'returned'    => 'Returned',
                ];
            @endphp

            {{-- Status banner --}}
            @if ($job->status === 'complete')
                <div class="rounded-2xl bg-green-50 border border-green-200 p-4 text-green-800 font-medium">
                    ✓ All inspections are complete. Please collect your items at your convenience.
                </div>
            @endif

            {{-- Summary --}}
            <div class="mobile-card sm:rounded-lg">
                <div class="mobile-card-body">
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div>
                            <p class="text-lg font-bold text-gray-900">{{ $job->job_number }}</p>
                            <p class="text-sm text-gray-500">{{ $job->created_at->format('d M Y') }}</p>
                        </div>
                        <span class="mobile-chip {{ $pill[$job->status] ?? '' }}">
                            {{ $label[$job->status] ?? $job->status }}
                        </span>
                    </div>

                    <div class="mobile-meta-grid">
                        <div class="mobile-meta-item">
                            <p class="mobile-meta-label">Dropped off</p>
                            <p class="mobile-meta-value">{{ $job->drop_off_at?->format('d M Y') ?? 'Not yet' }}</p>
                        </div>
                        <div class="mobile-meta-item">
                            <p class="mobile-meta-label">Returned</p>
                            <p class="mobile-meta-value">{{ $job->return_at?->format('d M Y') ?? 'Not yet' }}</p>
                        </div>
                    </div>

                    {{-- Progress bar --}}
                    <div class="mt-5">
                        <div class="flex items-center justify-between text-sm text-gray-600 mb-1">
                            <span>Inspection progress</span>
                            <span>{{ $progress['done'] }} / {{ $progress['total'] }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            @php $pct = $progress['total'] > 0 ? round(($progress['done'] / $progress['total']) * 100) : 0; @endphp
                            <div class="bg-green-500 h-2.5 rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Items --}}
            <div class="mobile-card sm:rounded-lg">
                <div class="mobile-card-body">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Your Kit Items</h3>

                    <div class="space-y-3">
                        @foreach ($job->kitItems as $item)
                            @php
                                $jobInspection = $item->inspections->first();
                                $done = $jobInspection?->status === 'complete';
                                $result = $jobInspection?->overall_status;
                                $resultColor = match($result) {
                                    'pass' => 'text-green-600',
                                    'fail' => 'text-red-600',
                                    'conditional' => 'text-yellow-600',
                                    default => 'text-gray-400',
                                };
                            @endphp
                            <div class="flex items-center justify-between gap-3 py-2 border-b border-gray-100 last:border-0">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $item->typeName() }}</p>
                                    @if ($item->asset_tag || $item->serial_no)
                                        <p class="text-xs text-gray-500">{{ $item->asset_tag ?? $item->serial_no }}</p>
                                    @endif
                                </div>
                                <div class="text-right shrink-0">
                                    @if ($done)
                                        <span class="text-xs font-medium {{ $resultColor }}">
                                            {{ ucfirst($result ?? 'Done') }}
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400">Pending</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="mobile-action-group">
                <a href="{{ route('portal.jobs.index') }}" class="mobile-action-link">← Back to My Jobs</a>
            </div>

        </div>
    </div>
</x-app-layout>
