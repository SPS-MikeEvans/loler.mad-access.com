<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            My Portal — {{ $client?->name ?? 'Client Portal' }}
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-7xl mx-auto mobile-shell">

            @if (session('success'))
                <div class="mb-4 px-4 py-3 bg-green-100 text-green-800 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                <div class="mobile-stat-card {{ $overdue > 0 ? 'border-red-400' : '' }}">
                    <p class="text-sm text-gray-500">Overdue</p>
                    <p class="text-3xl font-bold {{ $overdue > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ $overdue }}</p>
                </div>
                <div class="mobile-stat-card {{ $dueSoon > 0 ? 'border-yellow-400' : '' }}">
                    <p class="text-sm text-gray-500">Due Within 30 Days</p>
                    <p class="text-3xl font-bold {{ $dueSoon > 0 ? 'text-yellow-600' : 'text-gray-900' }}">{{ $dueSoon }}</p>
                </div>
                <div class="mobile-stat-card {{ $flagged > 0 ? 'border-orange-400' : '' }}">
                    <p class="text-sm text-gray-500">Flagged for Inspection</p>
                    <p class="text-3xl font-bold {{ $flagged > 0 ? 'text-orange-500' : 'text-gray-900' }}">{{ $flagged }}</p>
                </div>
                <div class="mobile-stat-card {{ $pending > 0 ? 'border-blue-400' : '' }}">
                    <p class="text-sm text-gray-500">Pending Review</p>
                    <p class="text-3xl font-bold {{ $pending > 0 ? 'text-blue-600' : 'text-gray-900' }}">{{ $pending }}</p>
                </div>
            </div>

            @if ($overdue > 0)
                <div class="mb-6 px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                    You have {{ $overdue }} {{ Str::plural('item', $overdue) }} with overdue inspections. Please log any items for inspection as soon as possible to remain LOLER compliant.
                </div>
            @endif

            <div class="mobile-card overflow-hidden sm:rounded-lg">
                <div class="mobile-card-body">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Links</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <a href="{{ route('portal.kit.index') }}"
                           class="flex items-center gap-3 px-4 py-3 bg-brand-navy text-white rounded-xl hover:bg-brand-red transition">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            View My Equipment
                        </a>
                        <a href="{{ route('portal.kit.create') }}"
                           class="flex items-center gap-3 px-4 py-3 bg-gray-100 text-gray-800 rounded-xl hover:bg-gray-200 transition">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add New Equipment
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
