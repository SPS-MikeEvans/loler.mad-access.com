<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Request Inspection — Step 2 of 3
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-3xl mx-auto mobile-shell">
            <div class="mobile-card sm:rounded-lg">
                <div class="mobile-card-body">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Choose Drop-off Date</h3>
                    <p class="text-sm text-gray-600 mb-4">Pick when you'd like to drop the equipment off for inspection. Must be within the next 4 weeks.</p>

                    @if ($errors->any())
                        <div class="mb-4 px-4 py-3 bg-red-100 text-red-800 rounded-lg text-sm">
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    <p class="text-sm text-gray-500 mb-4">{{ count($selectedIds) }} {{ Str::plural('item', count($selectedIds)) }} selected.</p>

                    <form method="POST" action="{{ route('portal.jobs.review') }}" class="space-y-5">
                        @csrf

                        <div>
                            <x-input-label for="drop_off_at" :value="__('Preferred Drop-off Date')" />
                            <x-text-input id="drop_off_at" name="drop_off_at" type="date"
                                min="{{ $minDate }}" max="{{ $maxDate }}"
                                class="mt-1 block w-full" :value="old('drop_off_at', $dropOffAt)" required />
                            <x-input-error :messages="$errors->get('drop_off_at')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="notes" :value="__('Notes (optional)')" />
                            <textarea id="notes" name="notes" rows="3" maxlength="2000"
                                class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm text-sm focus:border-brand-red focus:ring-brand-red"
                                placeholder="Anything our team should know about this inspection?">{{ old('notes') }}</textarea>
                            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                        </div>

                        <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3 pt-4">
                            <a href="{{ route('portal.jobs.create') }}" class="text-sm text-brand-navy hover:text-brand-red text-center">← Back</a>
                            <x-primary-button>Next: Review →</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
