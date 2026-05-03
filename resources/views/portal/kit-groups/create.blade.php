<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            New Kit Group
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-3xl mx-auto mobile-shell">
            <div class="mobile-card sm:rounded-lg">
                <div class="mobile-card-body">
                    <form method="POST" action="{{ route('portal.kit-groups.store') }}" class="space-y-6">
                        @csrf

                        <div>
                            <x-input-label for="name" :value="__('Group Name')" />
                            <x-text-input id="name" name="name" type="text"
                                class="mt-1 block w-full" :value="old('name')" maxlength="100" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            <p class="mt-1 text-xs text-gray-500">e.g. "Personal Kit Set", "Carabiners – Bag A".</p>
                        </div>

                        <div>
                            <x-input-label for="description" :value="__('Description (optional)')" />
                            <textarea id="description" name="description" rows="3" maxlength="1000"
                                class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm text-sm focus:border-brand-red focus:ring-brand-red">{{ old('description') }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label :value="__('Items in this group')" />
                            <p class="mt-1 mb-3 text-xs text-gray-500">Pick equipment that is currently not in any other group.</p>

                            @if ($assignableItems->isEmpty())
                                <p class="text-sm text-gray-500 italic">No ungrouped equipment available. Create some equipment first or move items out of an existing group.</p>
                            @else
                                <div class="space-y-2 max-h-96 overflow-y-auto border border-gray-200 rounded-lg p-3">
                                    @foreach ($assignableItems as $item)
                                        <label class="flex items-center gap-3 px-2 py-1.5 hover:bg-gray-50 rounded cursor-pointer">
                                            <input type="checkbox" name="kit_item_ids[]" value="{{ $item->id }}"
                                                @checked(in_array($item->id, old('kit_item_ids', []), false))
                                                class="rounded border-gray-300 text-brand-red focus:ring-brand-red" />
                                            <span class="text-sm">
                                                <span class="font-medium text-gray-900">{{ $item->typeName() }}</span>
                                                <span class="text-gray-500">— {{ $item->asset_tag ?? $item->serial_no ?? "#{$item->id}" }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                <x-input-error :messages="$errors->get('kit_item_ids')" class="mt-2" />
                                <x-input-error :messages="$errors->get('kit_item_ids.*')" class="mt-2" />
                            @endif
                        </div>

                        <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-3">
                            <a href="{{ route('portal.kit-groups.index') }}" class="text-sm text-brand-navy hover:text-brand-red text-center">Cancel</a>
                            <x-primary-button>Create Group</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
