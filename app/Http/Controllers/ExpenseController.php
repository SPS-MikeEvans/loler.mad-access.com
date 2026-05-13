<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\AuditLog;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:admin'])->only(['index', 'create', 'store', 'show', 'edit', 'update', 'downloadReceipt']);
        $this->middleware(['role:admin', 'password.confirm', 'throttle:destructive-actions'])->only('destroy');
    }

    public function index(Request $request): View
    {
        $expenses = Expense::query()
            ->with('category')
            ->when($request->filled('category'), fn ($q) => $q->where('expense_category_id', $request->integer('category')))
            ->when($request->boolean('unreconciled'), fn ($q) => $q->whereNull('reconciled_at'))
            ->orderByDesc('expense_date')
            ->paginate(25)
            ->withQueryString();

        $categories = ExpenseCategory::active()->orderBy('name')->get();

        return view('accounting.expenses.index', compact('expenses', 'categories'));
    }

    public function create(): View
    {
        $categories = ExpenseCategory::active()->orderBy('name')->get();

        return view('accounting.expenses.create', compact('categories'));
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $expense = Expense::create([
            'expense_date' => $data['expense_date'],
            'expense_category_id' => $data['expense_category_id'] ?? null,
            'supplier' => $data['supplier'],
            'amount' => $data['amount'],
            'notes' => $data['notes'] ?? null,
        ]);

        if ($request->hasFile('receipt')) {
            $expense->receipt_path = $this->storeReceipt($request, $expense);
            $expense->save();
        }

        AuditLog::record('created', 'Expense', $expense->id, "Recorded expense from {$expense->supplier} (£{$expense->amount})");

        return redirect()->route('accounting.expenses.show', $expense)->with('success', 'Expense recorded.');
    }

    public function show(Expense $expense): View
    {
        $expense->load('category', 'bankTransaction');

        $deleteConfirmation = $this->issueConfirmedAction(
            'delete.expense',
            'Expense',
            $expense->id,
            "DELETE-EXPENSE-{$expense->id}"
        );

        return view('accounting.expenses.show', compact('expense', 'deleteConfirmation'));
    }

    public function edit(Expense $expense): View
    {
        $categories = ExpenseCategory::active()->orderBy('name')->get();

        return view('accounting.expenses.edit', compact('expense', 'categories'));
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $data = $request->validated();

        $expense->fill([
            'expense_date' => $data['expense_date'],
            'expense_category_id' => $data['expense_category_id'] ?? null,
            'supplier' => $data['supplier'],
            'amount' => $data['amount'],
            'notes' => $data['notes'] ?? null,
        ]);

        if ($request->boolean('remove_receipt') && $expense->receipt_path) {
            Storage::disk(config('banking.expenses.receipt_disk', 'local'))->delete($expense->receipt_path);
            $expense->receipt_path = null;
        }

        if ($request->hasFile('receipt')) {
            if ($expense->receipt_path) {
                Storage::disk(config('banking.expenses.receipt_disk', 'local'))->delete($expense->receipt_path);
            }
            $expense->receipt_path = $this->storeReceipt($request, $expense);
        }

        $expense->save();

        AuditLog::record('updated', 'Expense', $expense->id, "Updated expense from {$expense->supplier}");

        return redirect()->route('accounting.expenses.show', $expense)->with('success', 'Expense updated.');
    }

    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        $confirmation = $this->makeConfirmedAction(
            'delete.expense',
            'Expense',
            $expense->id,
            "DELETE-EXPENSE-{$expense->id}"
        );

        if ($failure = $this->ensureConfirmedAction($request, $confirmation)) {
            return $failure;
        }

        AuditLog::record(
            'deleted',
            'Expense',
            $expense->id,
            "Deleted expense from {$expense->supplier} (£{$expense->amount})",
            [
                'confirmed_action' => $confirmation->actionKey,
                'confirmation_phrase' => $confirmation->phrase,
                'confirmed_by_user_id' => auth()->id(),
                'confirmed_at' => now()->toIso8601String(),
            ]
        );

        $expense->delete();

        return redirect()->route('accounting.expenses.index')->with('success', 'Expense deleted.');
    }

    public function downloadReceipt(Expense $expense): StreamedResponse
    {
        abort_unless($expense->receipt_path, 404);

        return Storage::disk(config('banking.expenses.receipt_disk', 'local'))
            ->download($expense->receipt_path);
    }

    private function storeReceipt(Request $request, Expense $expense): string
    {
        $disk = (string) config('banking.expenses.receipt_disk', 'local');

        return $request->file('receipt')->storeAs(
            "expenses/{$expense->id}",
            $request->file('receipt')->hashName(),
            ['disk' => $disk, 'visibility' => 'private'],
        );
    }
}
