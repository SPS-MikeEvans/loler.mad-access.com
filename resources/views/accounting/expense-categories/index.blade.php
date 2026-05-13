<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Expense Categories') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="px-4 py-3 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 space-y-4">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Manage Categories</h3>
                        <p class="mt-1 text-sm text-gray-600">Categories tag expenses for reporting. Inactive ones are hidden from the dropdown when creating new expenses.</p>
                    </div>

                    <form method="POST" action="{{ route('accounting.expense-categories.store') }}" class="flex flex-col sm:flex-row gap-3">
                        @csrf
                        <div class="flex-1">
                            <x-text-input id="name" name="name" type="text" required class="block w-full" placeholder="e.g. Subsistence" :value="old('name')" />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <x-primary-button type="submit">Add category</x-primary-button>
                    </form>

                    @if ($categories->isEmpty())
                        <p class="text-gray-500 italic">No categories yet — add your first above or run <code>php artisan accounting:setup</code>.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($categories as $cat)
                                        <tr>
                                            <td class="px-4 py-3 font-medium text-gray-900">{{ $cat->name }}</td>
                                            <td class="px-4 py-3">
                                                @if ($cat->is_active)
                                                    <span class="px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-800">Active</span>
                                                @else
                                                    <span class="px-2 py-0.5 text-xs rounded-full bg-gray-200 text-gray-700">Inactive</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <div class="inline-flex items-center gap-2">
                                                    <form method="POST" action="{{ route('accounting.expense-categories.update', $cat) }}" class="inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="is_active" value="{{ $cat->is_active ? '0' : '1' }}">
                                                        <button type="submit" class="text-sm text-brand-navy hover:text-brand-red">
                                                            {{ $cat->is_active ? 'Deactivate' : 'Activate' }}
                                                        </button>
                                                    </form>
                                                    <span class="text-gray-300">|</span>
                                                    <button type="button"
                                                            class="text-sm text-red-600 hover:text-red-800"
                                                            x-data
                                                            x-on:click="$dispatch('open-modal', '{{ $deleteConfirmations[$cat->id]->modalName }}')">
                                                        Delete
                                                    </button>
                                                </div>
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
    </div>

    @foreach ($categories as $cat)
        <x-confirmed-action-modal
            :name="$deleteConfirmations[$cat->id]->modalName"
            title="Delete Category"
            :message="'Delete the \''.$cat->name.'\' category. Linked expenses keep their data but lose this label. Type the phrase below to continue.'"
            :phrase="$deleteConfirmations[$cat->id]->phrase"
            :action="route('accounting.expense-categories.destroy', $cat)"
            method="DELETE"
            submit-label="Delete Category"
            :password-confirm="true"
        />
    @endforeach
</x-app-layout>
