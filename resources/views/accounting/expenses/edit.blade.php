<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Edit Expense') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('accounting.expenses.update', $expense) }}" enctype="multipart/form-data" class="space-y-5">
                    @csrf
                    @method('PUT')
                    @include('accounting.expenses._form', ['expense' => $expense])
                    @if ($expense->receipt_path)
                        <label class="flex items-center gap-2 text-sm text-red-700">
                            <input type="checkbox" name="remove_receipt" value="1" class="rounded border-gray-300">
                            <span>Remove existing receipt</span>
                        </label>
                    @endif
                    <div class="flex justify-end gap-3">
                        <a href="{{ route('accounting.expenses.show', $expense) }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Cancel</a>
                        <x-primary-button type="submit">Save changes</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
