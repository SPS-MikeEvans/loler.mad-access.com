<x-mobile-layout title="Start Inspection">
    <div class="min-h-screen flex flex-col bg-slate-100">

        {{-- Header --}}
        <header class="bg-brand-navy text-white px-4 py-4 flex items-center gap-3">
            <x-application-logo class="w-8 h-8 fill-current text-white/70" />
            <div class="min-w-0">
                <p class="text-[11px] uppercase tracking-[0.18em] text-white/55">Mobile Inspection</p>
                <span class="font-semibold text-lg">{{ config('app.name') }}</span>
            </div>
        </header>

        <div class="flex-1 px-4 py-6 space-y-5 mobile-bottom-safe">

            {{-- Competency warning --}}
            @if($competencyWarning)
                <div class="bg-red-50 border border-red-200 rounded-xl p-4 flex gap-3">
                    <svg class="w-5 h-5 text-red-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    <div>
                        <p class="font-semibold text-red-800 text-sm">Not authorised to inspect</p>
                        <p class="text-red-700 text-sm mt-0.5">{{ $competencyWarning }}</p>
                    </div>
                </div>
            @endif

            {{-- Kit details card --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold text-brand-navy uppercase tracking-[0.18em] mb-1">{{ $kitItem->kitType->category }}</p>
                <h1 class="text-xl font-bold text-gray-900">{{ $kitItem->kitType->name }}</h1>
                <p class="text-gray-500 text-sm mt-0.5">{{ $kitItem->client->name }}</p>
                <p class="mt-3 text-sm leading-6 text-slate-600">Everything needed to begin the field inspection is stacked below, with key asset details surfaced first.</p>

                <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-gray-400 text-xs uppercase tracking-wide">Asset Tag</p>
                        <p class="font-semibold">{{ $kitItem->asset_tag ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs uppercase tracking-wide">Serial No.</p>
                        <p class="font-semibold">{{ $kitItem->serial_no ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs uppercase tracking-wide">SWL</p>
                        <p class="font-semibold">{{ $kitItem->swl_kg ? $kitItem->swl_kg . ' kg' : '—' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs uppercase tracking-wide">Next Due</p>
                        <p class="font-semibold {{ $kitItem->next_inspection_due?->isPast() ? 'text-red-600' : '' }}">
                            {{ $kitItem->next_inspection_due?->format('d M Y') ?? '—' }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Resume draft --}}
            @if($draft)
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                    <p class="text-sm font-semibold text-amber-800">Draft in progress</p>
                    <p class="text-xs text-amber-700 mt-0.5">Started {{ $draft->started_at->diffForHumans() }}</p>
                </div>
                <a href="{{ route('mobile.inspect.wizard', [$draft, 0]) }}"
                   class="block w-full text-center bg-amber-500 hover:bg-amber-600 text-white font-semibold text-lg py-4 rounded-2xl transition">
                    Resume Inspection
                </a>
                <div class="text-center">
                    <span class="text-gray-400 text-sm">or</span>
                </div>
            @endif

            {{-- Start button --}}
            @if(! $competencyWarning)
                <form method="POST" action="{{ route('mobile.inspect.create-draft', $kitItem) }}">
                    @csrf
                    <button type="submit"
                            class="w-full bg-brand-navy hover:bg-brand-red text-white font-semibold text-lg py-4 rounded-2xl transition {{ $draft ? 'bg-gray-200 text-gray-500 hover:bg-gray-300' : '' }}">
                        {{ $draft ? 'Start New Inspection' : 'Start Inspection' }}
                    </button>
                </form>
            @endif

            @error('competency')
                <p class="text-red-600 text-sm text-center">{{ $message }}</p>
            @enderror

        </div>

        {{-- Inspector info --}}
        <footer class="px-4 py-4 text-center text-xs text-gray-400 border-t border-gray-100">
            Signed in as {{ auth()->user()->name }}
        </footer>

    </div>
</x-mobile-layout>
