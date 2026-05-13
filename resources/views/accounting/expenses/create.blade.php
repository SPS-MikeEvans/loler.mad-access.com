<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Add Expense') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('accounting.expenses.store') }}" enctype="multipart/form-data" class="space-y-5">
                    @csrf
                    @include('accounting.expenses._form', ['expense' => null])
                    <div class="flex justify-end gap-3">
                        <a href="{{ route('accounting.expenses.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Cancel</a>
                        <x-primary-button type="submit">Save expense</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
