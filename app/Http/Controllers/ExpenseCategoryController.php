<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseCategoryRequest;
use App\Http\Requests\UpdateExpenseCategoryRequest;
use App\Models\AuditLog;
use App\Models\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:admin'])->only(['index', 'store', 'update']);
        $this->middleware(['role:admin', 'password.confirm', 'throttle:destructive-actions'])->only('destroy');
    }

    public function index(): View
    {
        $categories = ExpenseCategory::orderBy('name')->get();
        $deleteConfirmations = $categories->mapWithKeys(fn (ExpenseCategory $cat) => [
            $cat->id => $this->issueConfirmedAction(
                'delete.expense-category',
                'ExpenseCategory',
                $cat->id,
                "DELETE-EXPENSE-CATEGORY-{$cat->id}"
            ),
        ]);

        return view('accounting.expense-categories.index', compact('categories', 'deleteConfirmations'));
    }

    public function store(StoreExpenseCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $category = ExpenseCategory::create([
            'name' => $data['name'],
            'is_active' => true,
            'notes' => $data['notes'] ?? null,
        ]);

        AuditLog::record('created', 'ExpenseCategory', $category->id, "Added expense category {$category->name}");

        return redirect()->route('accounting.expense-categories.index')->with('success', "Category {$category->name} added.");
    }

    public function update(UpdateExpenseCategoryRequest $request, ExpenseCategory $expenseCategory): RedirectResponse
    {
        $data = $request->validated();

        $expenseCategory->update(array_filter([
            'name' => $data['name'] ?? null,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : null,
            'notes' => $data['notes'] ?? null,
        ], fn ($v) => $v !== null));

        return redirect()->route('accounting.expense-categories.index')->with('success', "Category {$expenseCategory->name} updated.");
    }

    public function destroy(Request $request, ExpenseCategory $expenseCategory): RedirectResponse
    {
        $confirmation = $this->makeConfirmedAction(
            'delete.expense-category',
            'ExpenseCategory',
            $expenseCategory->id,
            "DELETE-EXPENSE-CATEGORY-{$expenseCategory->id}"
        );

        if ($failure = $this->ensureConfirmedAction($request, $confirmation)) {
            return $failure;
        }

        AuditLog::record(
            'deleted',
            'ExpenseCategory',
            $expenseCategory->id,
            "Deleted expense category {$expenseCategory->name}",
            [
                'confirmed_action' => $confirmation->actionKey,
                'confirmation_phrase' => $confirmation->phrase,
                'confirmed_by_user_id' => auth()->id(),
                'confirmed_at' => now()->toIso8601String(),
            ]
        );

        $expenseCategory->delete();

        return redirect()->route('accounting.expense-categories.index')->with('success', 'Category deleted.');
    }
}
