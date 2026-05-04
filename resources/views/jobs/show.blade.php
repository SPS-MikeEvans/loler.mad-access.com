<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-white leading-tight">
                {{ $job->job_number }}
            </h2>
            @php
                $pill = [
                    'draft'       => 'bg-gray-200 text-gray-700',
                    'open'        => 'bg-blue-200 text-blue-800',
                    'in_progress' => 'bg-yellow-200 text-yellow-800',
                    'complete'    => 'bg-green-200 text-green-800',
                    'returned'    => 'bg-slate-200 text-slate-700',
                ];
            @endphp
            <span class="px-3 py-1 rounded-full text-sm font-medium {{ $pill[$job->status] ?? '' }}">
                {{ ucfirst(str_replace('_', ' ', $job->status)) }}
            </span>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-4xl mx-auto mobile-shell mobile-stack">

            @if (session('success'))
                <div class="mb-4 px-4 py-3 bg-green-100 text-green-800 rounded-lg">
                    {{ session('success') }}
                    @if (session('show_print_label'))
                        <a href="{{ route('jobs.bag-label', $job) }}" target="_blank" class="ml-2 underline font-medium">Print bag label →</a>
                    @endif
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 px-4 py-3 bg-red-100 text-red-800 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Summary card --}}
            <div class="mobile-card sm:rounded-lg">
                <div class="mobile-card-body">
                    <div class="mobile-meta-grid">
                        <div class="mobile-meta-item">
                            <p class="mobile-meta-label">Client</p>
                            <p class="mobile-meta-value">
                                <a href="{{ route('clients.show', $job->client) }}" class="text-brand-navy hover:underline">{{ $job->client->name }}</a>
                            </p>
                        </div>
                        <div class="mobile-meta-item">
                            <p class="mobile-meta-label">Created</p>
                            <p class="mobile-meta-value">{{ $job->created_at->format('d M Y') }} by {{ $job->createdBy?->name ?? '—' }}</p>
                        </div>
                        <div class="mobile-meta-item">
                            <p class="mobile-meta-label">Drop-off</p>
                            <p class="mobile-meta-value">
                                @if ($job->drop_off_at)
                                    {{ $job->drop_off_at->format('d M Y H:i') }} — {{ $job->drop_off_signed_by }}
                                @else
                                    <span class="text-gray-400">Not signed</span>
                                @endif
                            </p>
                        </div>
                        <div class="mobile-meta-item">
                            <p class="mobile-meta-label">Return</p>
                            <p class="mobile-meta-value">
                                @if ($job->return_at)
                                    {{ $job->return_at->format('d M Y H:i') }} — {{ $job->return_signed_by }}
                                @else
                                    <span class="text-gray-400">Not signed</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    @if ($job->notes)
                        <p class="mt-4 text-sm text-gray-600">{{ $job->notes }}</p>
                    @endif

                    {{-- Inspection progress --}}
                    <div class="mt-5 flex items-center gap-4">
                        <div class="flex-1 bg-gray-200 rounded-full h-2.5">
                            @php $pct = $progress['total'] > 0 ? round(($progress['done'] / $progress['total']) * 100) : 0; @endphp
                            <div class="bg-green-500 h-2.5 rounded-full transition-all" style="width: {{ $pct }}%"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-700 whitespace-nowrap">
                            {{ $progress['done'] }}/{{ $progress['total'] }} inspected
                            @if ($progress['flagged'] > 0)
                                · <span class="text-red-600">{{ $progress['flagged'] }} quarantined</span>
                            @endif
                        </span>
                    </div>

                    {{-- Actions --}}
                    <div class="mt-5 flex flex-wrap gap-3">
                        @if (in_array($job->status, ['draft', 'open']))
                            <a href="{{ route('jobs.edit', $job) }}">
                                <x-secondary-button>Edit Items</x-secondary-button>
                            </a>
                        @endif

                        @if (in_array($job->status, ['draft', 'open', 'in_progress']))
                            <x-secondary-button
                                type="button"
                                x-data
                                x-on:click="$dispatch('open-modal', 'add-kit-to-job')">
                                Add Kit
                            </x-secondary-button>
                        @endif

                        @if (in_array($job->status, ['draft', 'open']))
                            <a href="{{ route('jobs.sign-drop-off', $job) }}">
                                <x-primary-button>Sign Drop-Off</x-primary-button>
                            </a>
                        @endif

                        @if ($job->status === 'complete')
                            <a href="{{ route('jobs.sign-return', $job) }}">
                                <x-primary-button>Sign Return</x-primary-button>
                            </a>
                        @endif

                        <a href="{{ route('jobs.bag-label', $job) }}" target="_blank">
                            <x-secondary-button>Print Bag Label</x-secondary-button>
                        </a>

                        @if (auth()->user()->isAdmin() && $deleteConfirmation)
                            <button type="button"
                                class="px-4 py-2 rounded border border-red-300 text-red-600 text-sm hover:bg-red-50"
                                x-data
                                x-on:click="$dispatch('open-modal', '{{ $deleteConfirmation->modalName }}')">
                                Delete Job
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            {{-- QR Code --}}
            <div class="mobile-card sm:rounded-lg">
                <div class="mobile-card-body">
                    <h3 class="text-base font-semibold text-gray-900 mb-3">Bag QR Code</h3>
                    <div class="flex items-start gap-6">
                        <div class="shrink-0">{!! $qrSvg !!}</div>
                        <div>
                            <p class="text-sm text-gray-600 mb-2">Scan to open this job. Print the bag label and attach it to the storage bag.</p>
                            <a href="{{ route('jobs.bag-label', $job) }}" target="_blank">
                                <x-secondary-button>Print Bag Label</x-secondary-button>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kit items --}}
            <div class="mobile-card overflow-hidden sm:rounded-lg">
                <div class="mobile-card-body">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">
                        Kit Items
                        <span class="ml-2 px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-700">{{ $job->kitItems->count() }}</span>
                    </h3>

                    @if ($job->kitItems->isEmpty())
                        <p class="text-sm text-gray-400 italic">No items added to this job yet.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Asset / Serial</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Inspection</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Drop-off Notes</th>
                                        <th class="px-4 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @foreach ($job->kitItems as $item)
                                        @php
                                            $jobInspection = $item->inspections->first();
                                            $inspStatus = $jobInspection?->overall_status;
                                            $inspStatusClass = match($inspStatus) {
                                                'pass' => 'text-green-600',
                                                'fail' => 'text-red-600',
                                                'conditional' => 'text-yellow-600',
                                                default => 'text-gray-400',
                                            };
                                            $availableForItem = $availablePreJobInspections->get($item->id, collect());
                                            $canMarkDone = ! $jobInspection
                                                && in_array($job->status, ['open', 'in_progress'], true);
                                            $modalName = "mark-done-{$item->id}";
                                        @endphp
                                        <tr>
                                            <td class="px-4 py-3 font-medium text-gray-900">{{ $item->typeName() }}</td>
                                            <td class="px-4 py-3 text-gray-600">{{ $item->asset_tag ?? $item->serial_no ?? '—' }}</td>
                                            <td class="px-4 py-3 {{ $inspStatusClass }}">
                                                @if ($jobInspection)
                                                    {{ ucfirst($jobInspection->overall_status) }}
                                                    @if ($jobInspection->status === 'complete')
                                                        <span class="text-gray-400 font-normal"> · done</span>
                                                    @else
                                                        <span class="text-gray-400 font-normal"> · in progress</span>
                                                    @endif
                                                @else
                                                    <span>Pending</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-gray-500 max-w-xs">{{ $item->pivot->condition_notes ?: '—' }}</td>
                                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                                @if ($canMarkDone)
                                                    @if ($availableForItem->count() === 1)
                                                        {{-- Exactly one unlinked inspection: one-click POST --}}
                                                        <form method="POST" action="{{ route('jobs.kit-items.mark-done', [$job, $item]) }}" class="inline">
                                                            @csrf
                                                            <input type="hidden" name="inspection_id" value="{{ $availableForItem->first()->id }}">
                                                            <button type="submit"
                                                                    title="Link inspection from {{ $availableForItem->first()->inspection_date?->format('d M Y') }}"
                                                                    class="text-green-700 hover:text-green-900 text-xs font-medium mr-3">
                                                                Mark done
                                                            </button>
                                                        </form>
                                                    @elseif ($availableForItem->count() > 1)
                                                        {{-- Multiple unlinked inspections: open picker modal --}}
                                                        <button type="button"
                                                                x-data
                                                                x-on:click="$dispatch('open-modal', '{{ $modalName }}')"
                                                                class="text-green-700 hover:text-green-900 text-xs font-medium mr-3">
                                                            Mark done…
                                                        </button>
                                                    @else
                                                        {{-- No unlinked inspections available --}}
                                                        <span class="text-xs text-gray-400 mr-3 cursor-not-allowed"
                                                              title="No completed pre-job inspection found for this item.">
                                                            Mark done
                                                        </span>
                                                    @endif
                                                @endif
                                                <a href="{{ route('clients.kit-items.show', [$job->client, $item]) }}"
                                                   class="text-brand-navy hover:text-brand-red text-xs font-medium">View →</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Photos --}}
            <div class="mobile-card sm:rounded-lg">
                <div class="mobile-card-body">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Chain-of-Custody Photos</h3>

                    @php $dropPhotos = $job->photos->where('phase', 'drop_off'); $returnPhotos = $job->photos->where('phase', 'return'); @endphp

                    @if ($dropPhotos->isEmpty() && $returnPhotos->isEmpty())
                        <p class="text-sm text-gray-400 italic mb-4">No photos uploaded.</p>
                    @else
                        @if ($dropPhotos->isNotEmpty())
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Drop-off photos</p>
                            <div class="flex flex-wrap gap-2 mb-4">
                                @foreach ($dropPhotos as $photo)
                                    <div class="relative group">
                                        <img src="{{ Storage::disk('local')->url($photo->path) }}" class="h-20 w-20 rounded object-cover border">
                                        <form method="POST" action="{{ route('jobs.photos.destroy', [$job, $photo]) }}" class="hidden group-hover:block absolute top-0 right-0">
                                            @csrf @method('DELETE')
                                            <button class="bg-red-600 text-white text-xs rounded p-0.5">✕</button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        @if ($returnPhotos->isNotEmpty())
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Return photos</p>
                            <div class="flex flex-wrap gap-2 mb-4">
                                @foreach ($returnPhotos as $photo)
                                    <div class="relative group">
                                        <img src="{{ Storage::disk('local')->url($photo->path) }}" class="h-20 w-20 rounded object-cover border">
                                        <form method="POST" action="{{ route('jobs.photos.destroy', [$job, $photo]) }}" class="hidden group-hover:block absolute top-0 right-0">
                                            @csrf @method('DELETE')
                                            <button class="bg-red-600 text-white text-xs rounded p-0.5">✕</button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endif

                    <form method="POST" action="{{ route('jobs.photos.store', $job) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="flex flex-wrap items-end gap-3">
                            <div>
                                <x-input-label for="phase" value="Phase" />
                                <select name="phase" id="phase" class="mt-1 border-gray-300 rounded-md text-sm">
                                    <option value="drop_off">Drop-off</option>
                                    <option value="return">Return</option>
                                </select>
                            </div>
                            <div>
                                <x-input-label for="photos" value="Photos (max 4)" />
                                <input type="file" name="photos[]" id="photos" multiple accept="image/*" class="mt-1 text-sm">
                            </div>
                            <x-secondary-button type="submit">Upload</x-secondary-button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>

    @if (auth()->user()->isAdmin() && $deleteConfirmation)
        <x-confirmed-action-modal
            :name="$deleteConfirmation->modalName"
            title="Delete Job"
            :message="'This permanently deletes job '.$job->job_number.'. Type the phrase below to continue.'"
            :phrase="$deleteConfirmation->phrase"
            :action="route('jobs.destroy', $job)"
            method="DELETE"
            submit-label="Delete Job"
            :password-confirm="true"
        />
    @endif

    @if (in_array($job->status, ['draft', 'open', 'in_progress']))
        <x-modal name="add-kit-to-job" maxWidth="lg" focusable>
            <form method="POST" action="{{ route('jobs.kit-items.store', $job) }}" class="p-6 space-y-5">
                @csrf

                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Add kit to {{ $job->job_number }}</h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Select active kit from {{ $job->client->name }} that is not already on this job.
                    </p>
                </div>

                @if ($addableKitItems->isEmpty())
                    <p class="text-sm text-gray-500 italic">No additional active kit is available for this client.</p>
                @else
                    <div class="max-h-80 space-y-2 overflow-y-auto rounded-lg border border-gray-200 p-3">
                        @foreach ($addableKitItems as $item)
                            <label class="flex items-start gap-3 rounded px-2 py-1.5 hover:bg-gray-50">
                                <input type="checkbox"
                                       name="kit_item_ids[]"
                                       value="{{ $item->id }}"
                                       class="mt-0.5 rounded border-gray-300 text-brand-red focus:ring-brand-red">
                                <span class="text-sm text-gray-800">
                                    <span class="font-medium">{{ $item->typeName() }}</span>
                                    <span class="text-gray-500">- {{ $item->asset_tag ?? $item->serial_no ?? "#{$item->id}" }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('kit_item_ids')" class="mt-2" />
                    <x-input-error :messages="$errors->get('kit_item_ids.*')" class="mt-2" />
                @endif

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                    <button type="button"
                            x-on:click="$dispatch('close-modal', 'add-kit-to-job')"
                            class="text-sm text-gray-600 hover:text-gray-900">
                        Cancel
                    </button>
                    @if ($addableKitItems->isNotEmpty())
                        <x-primary-button>Add Selected Kit</x-primary-button>
                    @endif
                </div>
            </form>
        </x-modal>
    @endif

    {{-- Mark-done picker modals (only for items with multiple unlinked inspections) --}}
    @foreach ($job->kitItems as $item)
        @php $available = $availablePreJobInspections->get($item->id, collect()); @endphp
        @if ($available->count() > 1 && in_array($job->status, ['open', 'in_progress'], true) && $item->inspections->isEmpty())
            <x-modal :name="'mark-done-'.$item->id" maxWidth="md" focusable>
                <form method="POST" action="{{ route('jobs.kit-items.mark-done', [$job, $item]) }}" class="p-6 space-y-4">
                    @csrf
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Mark item done</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            {{ $item->typeName() }} has {{ $available->count() }} completed inspections that aren't linked to a job. Pick which one applies to this job.
                        </p>
                    </div>

                    <div>
                        <x-input-label :for="'inspection-pick-'.$item->id" value="Inspection to link" />
                        <select :id="'inspection-pick-'.$item->id" name="inspection_id" required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-brand-red focus:ring-brand-red">
                            @foreach ($available as $insp)
                                <option value="{{ $insp->id }}">
                                    {{ $insp->inspection_date?->format('d M Y') ?? '—' }}
                                    · {{ ucfirst($insp->overall_status ?? '—') }}
                                    @if ($insp->inspector?->name)
                                        · {{ $insp->inspector->name }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex justify-end gap-3">
                        <x-secondary-button type="button"
                                            x-on:click="$dispatch('close-modal', 'mark-done-{{ $item->id }}')">
                            Cancel
                        </x-secondary-button>
                        <x-primary-button>Link to this Job</x-primary-button>
                    </div>
                </form>
            </x-modal>
        @endif
    @endforeach
</x-app-layout>
