<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Edit Kit Group
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-3xl mx-auto mobile-shell space-y-6">

            @if (session('success'))
                <div class="px-4 py-3 bg-green-100 text-green-800 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mobile-card sm:rounded-lg">
                <div class="mobile-card-body">
                    <form method="POST" action="{{ route('portal.kit-groups.update', $kitGroup) }}" class="space-y-6">
                        @csrf
                        @method('PATCH')

                        <div>
                            <x-input-label for="name" :value="__('Group Name')" />
                            <x-text-input id="name" name="name" type="text"
                                class="mt-1 block w-full" :value="old('name', $kitGroup->name)" maxlength="100" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="description" :value="__('Description (optional)')" />
                            <textarea id="description" name="description" rows="3" maxlength="1000"
                                class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm text-sm focus:border-brand-red focus:ring-brand-red">{{ old('description', $kitGroup->description) }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label :value="__('Items in this group')" />
                            <p class="mt-1 mb-3 text-xs text-gray-500">
                                Tick to keep an item in this group. Untick to remove it (it will be left ungrouped).
                                Items currently in other groups are not shown.
                            </p>

                            @php
                                $currentIds = $kitGroup->kitItems->pluck('id')->all();
                                $oldIds = old('kit_item_ids', $currentIds);
                            @endphp

                            @if ($assignableItems->isEmpty())
                                <p class="text-sm text-gray-500 italic">No items available to assign.</p>
                            @else
                                <div class="space-y-2 max-h-96 overflow-y-auto border border-gray-200 rounded-lg p-3">
                                    @foreach ($assignableItems as $item)
                                        <label class="flex items-center gap-3 px-2 py-1.5 hover:bg-gray-50 rounded cursor-pointer">
                                            <input type="checkbox" name="kit_item_ids[]" value="{{ $item->id }}"
                                                @checked(in_array($item->id, $oldIds, false))
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
                            <a href="{{ route('portal.kit-groups.show', $kitGroup) }}" class="text-sm text-brand-navy hover:text-brand-red text-center">Cancel</a>
                            <x-primary-button>Save Changes</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="mobile-card sm:rounded-lg border border-red-100">
                <div class="mobile-card-body">
                    <h3 class="text-base font-semibold text-gray-900 mb-2">Delete This Group</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Deleting a group leaves the equipment intact but removes them from this group.
                    </p>
                    <x-danger-button
                        type="button"
                        x-data
                        x-on:click="$dispatch('open-modal', '{{ $deleteConfirmation->modalName }}')"
                    >
                        Delete Group
                    </x-danger-button>
                </div>
            </div>

            <div class="px-1">
                <a href="{{ route('portal.kit-groups.index') }}" class="text-sm text-brand-navy hover:text-brand-red">← Back to Kit Groups</a>
            </div>
        </div>
    </div>

    <x-confirmed-action-modal
        :name="$deleteConfirmation->modalName"
        title="Delete Kit Group"
        :message="'This deletes the group '.$kitGroup->name.' and unassigns its items. Type the phrase below to continue.'"
        :phrase="$deleteConfirmation->phrase"
        :action="route('portal.kit-groups.destroy', $kitGroup)"
        method="DELETE"
        submit-label="Delete Group"
    />
</x-app-layout>
