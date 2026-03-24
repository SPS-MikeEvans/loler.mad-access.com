@props([
    'name',
    'title',
    'message',
    'phrase',
    'action',
    'method' => 'POST',
    'submitLabel' => 'Confirm',
    'passwordConfirm' => false,
    'hidden' => [],
])

<x-modal :name="$name" :show="session('open_confirmation_modal') === $name" maxWidth="md" focusable>
    <form method="POST" action="{{ $action }}" class="p-6 space-y-4">
        @csrf
        @if (! in_array(strtoupper($method), ['GET', 'POST'], true))
            @method($method)
        @endif

        @foreach ($hidden as $field => $value)
            <input type="hidden" name="{{ $field }}" value="{{ $value }}">
        @endforeach

        <div>
            <h2 class="text-lg font-semibold text-gray-900">{{ $title }}</h2>
            <p class="mt-2 text-sm text-gray-600">{{ $message }}</p>
        </div>

        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Type this confirmation phrase</p>
            <p class="mt-2 font-mono text-sm text-amber-900">{{ $phrase }}</p>
        </div>

        @if ($passwordConfirm)
            <p class="text-xs text-gray-500">
                You may be asked to confirm your password before this action completes.
            </p>
        @endif

        <div>
            <x-input-label for="{{ $name }}-confirmation_phrase" :value="__('Confirmation Phrase')" />
            <x-text-input
                id="{{ $name }}-confirmation_phrase"
                name="confirmation_phrase"
                type="text"
                class="mt-1 block w-full font-mono"
                :value="old('confirmation_phrase')"
                required
            />
            <x-input-error :messages="$errors->get('confirmation_phrase')" class="mt-2" />
        </div>

        <div class="flex justify-end gap-3">
            <x-secondary-button
                type="button"
                x-on:click="$dispatch('close-modal', '{{ $name }}')"
            >
                Cancel
            </x-secondary-button>
            <x-danger-button>{{ $submitLabel }}</x-danger-button>
        </div>
    </form>
</x-modal>
