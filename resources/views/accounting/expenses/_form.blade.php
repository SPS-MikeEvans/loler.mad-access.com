<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="expense_date" value="Date" />
        <x-text-input id="expense_date" name="expense_date" type="date" required class="mt-1 block w-full"
                      :value="old('expense_date', optional($expense?->expense_date)->format('Y-m-d') ?? now()->toDateString())" />
        <x-input-error :messages="$errors->get('expense_date')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="expense_category_id" value="Category" />
        <select id="expense_category_id" name="expense_category_id" class="mt-1 block w-full border-gray-300 rounded-md text-sm">
            <option value="">— None —</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->id }}" @selected(old('expense_category_id', $expense?->expense_category_id) == $cat->id)>{{ $cat->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('expense_category_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="supplier" value="Supplier / payee" />
        <x-text-input id="supplier" name="supplier" type="text" required class="mt-1 block w-full"
                      :value="old('supplier', $expense?->supplier)" placeholder="e.g. Screwfix" />
        <x-input-error :messages="$errors->get('supplier')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="amount" value="Amount (£)" />
        <x-text-input id="amount" name="amount" type="number" step="0.01" min="0" required class="mt-1 block w-full"
                      :value="old('amount', $expense?->amount)" />
        <x-input-error :messages="$errors->get('amount')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="notes" value="Notes (optional)" />
        <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('notes', $expense?->notes) }}</textarea>
        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="receipt" value="Receipt (PDF/JPG/PNG, max 5MB)" />
        <input id="receipt" name="receipt" type="file" accept="application/pdf,image/jpeg,image/png" class="mt-1 block w-full text-sm">
        <x-input-error :messages="$errors->get('receipt')" class="mt-2" />
        @if ($expense?->receipt_path)
            <p class="mt-2 text-xs text-gray-600">
                Current: <a href="{{ route('accounting.expenses.receipt', $expense) }}" class="text-brand-navy underline">download existing</a>
            </p>
        @endif
    </div>
</div>
