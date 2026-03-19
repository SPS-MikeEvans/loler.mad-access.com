<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ $kitItem->kitType->name }}
            @if ($kitItem->asset_tag)
                <span class="text-white/70 font-normal text-lg">({{ $kitItem->asset_tag }})</span>
            @endif
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

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
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Equipment Type</dt>
                            <dd class="mt-1 text-gray-900">{{ $kitItem->kitType->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Client</dt>
                            <dd class="mt-1 text-gray-900">
                                <a href="{{ route('clients.show', $client) }}" class="text-brand-navy hover:text-brand-red hover:underline">{{ $client->name }}</a>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Asset Tag</dt>
                            <dd class="mt-1 text-gray-900">{{ $kitItem->asset_tag ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Serial Number</dt>
                            <dd class="mt-1 text-gray-900">{{ $kitItem->serial_no ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Manufacturer</dt>
                            <dd class="mt-1 text-gray-900">{{ $kitItem->manufacturer ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Model</dt>
                            <dd class="mt-1 text-gray-900">{{ $kitItem->model ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Purchase Date</dt>
                            <dd class="mt-1 text-gray-900">{{ $kitItem->purchase_date?->format('d M Y') ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">First Use Date</dt>
                            <dd class="mt-1 text-gray-900">{{ $kitItem->first_use_date?->format('d M Y') ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Safe Working Load</dt>
                            <dd class="mt-1 text-gray-900">{{ $kitItem->swl_kg ? $kitItem->swl_kg . ' kg' : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Lifts People</dt>
                            <dd class="mt-1">
                                @if ($kitItem->lifting_people)
                                    <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">Yes</span>
                                @else
                                    <span class="px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded-full">No</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Status</dt>
                            <dd class="mt-1">
                                @php
                                    $statusColour = match($kitItem->status) {
                                        'in_service' => 'bg-green-100 text-green-800',
                                        'inspection_due' => 'bg-yellow-100 text-yellow-800',
                                        'quarantined' => 'bg-red-100 text-red-800',
                                        'retired' => 'bg-gray-100 text-gray-600',
                                        default => 'bg-gray-100 text-gray-600',
                                    };
                                @endphp
                                <span class="px-2 py-1 text-xs rounded-full {{ $statusColour }}">
                                    {{ ucfirst(str_replace('_', ' ', $kitItem->status)) }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Next Inspection Due</dt>
                            <dd class="mt-1 {{ $kitItem->next_inspection_due?->isPast() ? 'text-red-600 font-semibold' : 'text-gray-900' }}">
                                {{ $kitItem->next_inspection_due?->format('d M Y') ?? 'Not set' }}
                                @if ($kitItem->next_inspection_due?->isPast())
                                    <span class="text-xs font-normal ml-1">(overdue)</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="px-6 py-4 bg-gray-50 flex items-center gap-4 flex-wrap">
                    <a href="{{ route('clients.kit-items.inspections.create', [$client, $kitItem]) }}"
                       class="inline-flex items-center px-5 py-2.5 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 shadow-sm">
                        Start New LOLER Inspection →
                    </a>
                    <a href="{{ route('mobile.inspect.start', $kitItem) }}"
                       class="inline-flex items-center px-5 py-2.5 bg-brand-navy text-white font-medium rounded-lg hover:bg-brand-red shadow-sm transition duration-150">
                        📱 Start Mobile Inspection
                    </a>
                    <a href="{{ route('clients.kit-items.edit', [$client, $kitItem]) }}"
                       class="inline-flex items-center px-4 py-2 bg-amber-500 text-white text-sm font-medium rounded-lg hover:bg-amber-600">
                        Edit
                    </a>
                    <a href="{{ route('clients.kit-items.index', $client) }}" class="text-sm text-brand-navy hover:text-brand-red">
                        ← Back to Kit List
                    </a>
                </div>
            </div>

            {{-- QR Code --}}
            @if ($qrSvg)
                <div class="mt-6 bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">QR Code</h3>
                    <div class="flex items-start gap-6">
                        <div class="w-40 h-40 flex-shrink-0">{!! $qrSvg !!}</div>
                        <div class="text-sm text-gray-600">
                            <p>Scan this code to open the kit item page directly.</p>
                            <p class="mt-2 break-all text-xs text-gray-400">{{ $kitItem->qr_code }}</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Inspection history --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="font-semibold text-gray-900">Inspection History</h3>
                </div>
                @if ($kitItem->inspections->isEmpty())
                    <p class="px-6 py-4 text-sm text-gray-500">No inspections recorded yet.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inspector</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Result</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Next Due</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($kitItem->inspections as $inspection)
                                    @php
                                        $badge = match($inspection->overall_status) {
                                            'pass' => 'bg-green-100 text-green-800',
                                            'conditional' => 'bg-yellow-100 text-yellow-800',
                                            'fail' => 'bg-red-100 text-red-800',
                                            default => 'bg-gray-100 text-gray-600',
                                        };
                                        $label = match($inspection->overall_status) {
                                            'pass' => 'Pass',
                                            'conditional' => 'Conditional',
                                            'fail' => 'Fail',
                                            default => ucfirst($inspection->overall_status),
                                        };
                                    @endphp
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $inspection->inspection_date->format('d M Y') }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $inspection->inspector->name }}</td>
                                        <td class="px-4 py-3">
                                            <span class="px-2 py-1 text-xs rounded-full font-medium {{ $badge }}">{{ $label }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $inspection->next_due_date->format('d M Y') }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <a href="{{ route('clients.kit-items.inspections.show', [$client, $kitItem, $inspection]) }}"
                                               class="text-sm text-brand-navy hover:text-brand-red">View</a>
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
</x-app-layout>
