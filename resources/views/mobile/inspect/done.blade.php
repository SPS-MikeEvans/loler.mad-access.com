<x-mobile-layout title="Inspection Complete">
    <div class="min-h-screen flex flex-col items-center justify-center px-6 text-center">

        {{-- Result icon --}}
        @if($inspection->overall_status === 'pass')
            <div class="w-24 h-24 rounded-full bg-green-100 flex items-center justify-center mb-6">
                <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-green-700">Inspection Passed</h1>
            <p class="text-gray-500 mt-1">Equipment is safe for continued use.</p>
        @else
            <div class="w-24 h-24 rounded-full bg-red-100 flex items-center justify-center mb-6">
                <svg class="w-12 h-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-red-700">Inspection Failed</h1>
            <p class="text-gray-500 mt-1">Equipment has been quarantined.</p>
        @endif

        {{-- Summary --}}
        <div class="mt-6 bg-white rounded-xl border border-gray-100 shadow-sm p-5 w-full text-left">
            <p class="text-sm text-gray-500">{{ $inspection->kitItem->kitType->name }}</p>
            <p class="font-semibold text-gray-900">{{ $inspection->kitItem->asset_tag ?? $inspection->kitItem->serial_no ?? 'No tag' }}</p>
            <p class="text-sm text-gray-500 mt-0.5">{{ $inspection->kitItem->client->name }}</p>

            <div class="mt-4 grid grid-cols-3 gap-2 text-center text-sm">
                <div>
                    <p class="font-bold text-green-600 text-lg">{{ $inspection->checks->where('status', 'pass')->count() }}</p>
                    <p class="text-gray-400 text-xs">Pass</p>
                </div>
                <div>
                    <p class="font-bold text-red-600 text-lg">{{ $inspection->checks->where('status', 'fail')->count() }}</p>
                    <p class="text-gray-400 text-xs">Fail</p>
                </div>
                <div>
                    <p class="font-bold text-gray-500 text-lg">{{ $inspection->checks->where('status', 'n/a')->count() }}</p>
                    <p class="text-gray-400 text-xs">N/A</p>
                </div>
            </div>

            <p class="text-xs text-gray-400 mt-3">
                Next due: <strong>{{ $inspection->next_due_date->format('d M Y') }}</strong>
            </p>
        </div>

        {{-- Actions --}}
        <div class="mt-6 w-full space-y-3">
            @if($inspection->pdf_path)
                <a href="{{ route('inspections.pdf', $inspection) }}"
                   class="block w-full py-3.5 rounded-xl bg-brand-navy text-white font-semibold text-sm">
                    Download PDF Report
                </a>
            @endif

            <a href="{{ route('dashboard') }}"
               class="block w-full py-3.5 rounded-xl bg-gray-100 text-gray-700 font-semibold text-sm">
                Back to Dashboard
            </a>
        </div>

    </div>
</x-mobile-layout>
