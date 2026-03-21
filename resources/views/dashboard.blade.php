<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-7xl mx-auto mobile-shell mobile-stack">

            @if ($metrics)
                <div class="mobile-card sm:hidden">
                    <div class="mobile-card-body space-y-2">
                        <p class="mobile-kicker">Field Dashboard</p>
                        <h3 class="mobile-section-title">Inspection overview</h3>
                        <p class="text-sm text-slate-600">Key alerts and business totals arranged for quick decisions on site.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                    <div class="mobile-stat-card sm:rounded-lg">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Overdue</p>
                        <p class="mt-2 text-3xl font-bold {{ $metrics['overdue'] > 0 ? 'text-red-600' : 'text-gray-900' }}">
                            {{ $metrics['overdue'] }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500">Items past due date</p>
                    </div>

                    <div class="mobile-stat-card sm:rounded-lg">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Due Soon</p>
                        <p class="mt-2 text-3xl font-bold {{ $metrics['due_soon'] > 0 ? 'text-yellow-600' : 'text-gray-900' }}">
                            {{ $metrics['due_soon'] }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500">Due in next 30 days</p>
                    </div>

                    <div class="mobile-stat-card sm:rounded-lg">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue YTD</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">
                            £{{ number_format((float) $metrics['revenue_ytd'], 0) }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500">Inspection income {{ now()->year }}</p>
                    </div>

                    <div class="mobile-stat-card sm:rounded-lg">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Inspectors</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">{{ $metrics['inspectors'] }}</p>
                        <p class="mt-1 text-xs text-gray-500">Active inspector accounts</p>
                    </div>

                    <div class="mobile-stat-card sm:rounded-lg">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Certs Expiring</p>
                        <p class="mt-2 text-3xl font-bold {{ $metrics['expiring_certs'] > 0 ? 'text-amber-600' : 'text-gray-900' }}">
                            {{ $metrics['expiring_certs'] }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500">Inspector certs in 30 days</p>
                    </div>
                </div>

                @if ($metrics['overdue'] > 0)
                    <div class="rounded-2xl border border-red-200 bg-red-50 p-4 sm:rounded-lg sm:border-l-4 sm:border-red-400">
                        <p class="text-sm text-red-800">
                            <strong>{{ $metrics['overdue'] }} item{{ $metrics['overdue'] !== 1 ? 's are' : ' is' }} overdue for inspection.</strong>
                            <a href="{{ route('clients.index') }}" class="underline ml-1">View clients →</a>
                        </p>
                    </div>
                @endif

                @if ($metrics['flagged_items']->isNotEmpty())
                    <div class="mobile-card overflow-hidden sm:rounded-lg">
                        <div class="mobile-card-body">
                            <h3 class="text-base font-semibold text-gray-900 mb-3">
                                Items Flagged for Inspection by Clients
                                <span class="ml-2 px-2 py-0.5 text-xs rounded-full bg-orange-100 text-orange-700">{{ $metrics['flagged_items']->count() }}</span>
                            </h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Equipment</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Asset / Serial</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Notes</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
                                            <th class="px-4 py-2"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 bg-white">
                                        @foreach ($metrics['flagged_items'] as $item)
                                            <tr>
                                                <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900">{{ $item->client->name }}</td>
                                                <td class="px-4 py-3 whitespace-nowrap text-gray-700">{{ $item->kitType->name }}</td>
                                                <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $item->asset_tag ?? $item->serial_no ?? '—' }}</td>
                                                <td class="px-4 py-3 text-gray-600 max-w-xs">{{ Str::limit($item->flag_notes, 60) ?: '—' }}</td>
                                                <td class="px-4 py-3 whitespace-nowrap {{ $item->next_inspection_due?->isPast() ? 'text-red-600 font-semibold' : 'text-gray-600' }}">
                                                    {{ $item->next_inspection_due?->format('d M Y') ?? '—' }}
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    <a href="{{ route('clients.kit-items.show', [$item->client, $item]) }}"
                                                       class="text-brand-navy hover:text-brand-red text-xs font-medium">View →</a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($metrics['pending_items']->isNotEmpty())
                    <div class="mobile-card overflow-hidden sm:rounded-lg">
                        <div class="mobile-card-body">
                            <h3 class="text-base font-semibold text-gray-900 mb-3">
                                Client-Submitted Equipment Pending Review
                                <span class="ml-2 px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-700">{{ $metrics['pending_items']->count() }}</span>
                            </h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Equipment</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Asset / Serial</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Submitted</th>
                                            <th class="px-4 py-2"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 bg-white">
                                        @foreach ($metrics['pending_items'] as $item)
                                            <tr>
                                                <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900">{{ $item->client->name }}</td>
                                                <td class="px-4 py-3 whitespace-nowrap text-gray-700">{{ $item->kitType->name }}</td>
                                                <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $item->asset_tag ?? $item->serial_no ?? '—' }}</td>
                                                <td class="px-4 py-3 whitespace-nowrap text-gray-600">
                                                    {{ $item->created_at?->format('d M Y') ?? '—' }}
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    <a href="{{ route('clients.kit-items.show', [$item->client, $item]) }}"
                                                       class="text-brand-navy hover:text-brand-red text-xs font-medium">Review →</a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif

            @elseif (auth()->user()->isInspector())
                <div class="mobile-card">
                    <div class="mobile-card-body">
                    <h3 class="font-semibold text-gray-900 mb-3">Quick Links</h3>
                    <div class="mobile-action-group">
                        <a href="{{ route('clients.index') }}" class="text-brand-navy hover:text-brand-red text-sm font-medium">
                            View Clients & Kit Lists →
                        </a>
                    </div>
                </div>
                </div>

            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        {{ __("You're logged in!") }}
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
