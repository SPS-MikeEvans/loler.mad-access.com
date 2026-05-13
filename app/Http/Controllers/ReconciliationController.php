<?php

namespace App\Http\Controllers;

use App\Http\Requests\MatchReconciliationRequest;
use App\Models\AuditLog;
use App\Models\BankTransaction;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Reconciliation;
use App\Services\ReconciliationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReconciliationController extends Controller
{
    public function __construct(private readonly ReconciliationService $service)
    {
        $this->middleware(['role:admin'])->only(['index', 'match']);
        $this->middleware(['role:admin', 'password.confirm', 'throttle:destructive-actions'])->only('destroy');
    }

    public function index(): View
    {
        $unreconciledTransactions = BankTransaction::query()
            ->whereNull('reconciled_at')
            ->with('bankConnection')
            ->orderByDesc('booking_date')
            ->limit(50)
            ->get();

        $suggestionsByTx = $unreconciledTransactions->mapWithKeys(fn (BankTransaction $tx) => [
            $tx->id => $this->service->suggestMatches($tx),
        ]);

        $unpaidInvoices = Invoice::query()
            ->unpaid()
            ->with('client')
            ->orderBy('issued_date')
            ->limit(100)
            ->get();

        $unreconciledExpenses = Expense::query()
            ->unreconciled()
            ->with('category')
            ->orderByDesc('expense_date')
            ->limit(100)
            ->get();

        $recentReconciliations = Reconciliation::query()
            ->with(['bankTransaction', 'matchable'])
            ->latest()
            ->limit(25)
            ->get();

        $unmatchConfirmations = $recentReconciliations->mapWithKeys(fn (Reconciliation $r) => [
            $r->id => $this->issueConfirmedAction(
                'unmatch.reconciliation',
                'Reconciliation',
                $r->id,
                "UNMATCH-RECONCILIATION-{$r->id}"
            ),
        ]);

        return view('accounting.reconciliation.index', compact(
            'unreconciledTransactions',
            'suggestionsByTx',
            'unpaidInvoices',
            'unreconciledExpenses',
            'recentReconciliations',
            'unmatchConfirmations',
        ));
    }

    public function match(MatchReconciliationRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $tx = BankTransaction::findOrFail($data['bank_transaction_id']);
        $target = $data['matchable_type'] === 'invoice'
            ? Invoice::findOrFail($data['matchable_id'])
            : Expense::findOrFail($data['matchable_id']);

        $amount = isset($data['matched_amount']) && $data['matched_amount'] !== null
            ? (float) $data['matched_amount']
            : abs((float) $tx->amount);

        $this->service->match($tx, $target, $amount, $data['notes'] ?? null);

        return back()->with('success', 'Reconciliation recorded.');
    }

    public function destroy(Request $request, Reconciliation $reconciliation): RedirectResponse
    {
        $confirmation = $this->makeConfirmedAction(
            'unmatch.reconciliation',
            'Reconciliation',
            $reconciliation->id,
            "UNMATCH-RECONCILIATION-{$reconciliation->id}"
        );

        if ($failure = $this->ensureConfirmedAction($request, $confirmation)) {
            return $failure;
        }

        $this->service->unmatch($reconciliation);

        AuditLog::record(
            'unmatched',
            'Reconciliation',
            $reconciliation->id,
            "Unmatched reconciliation {$reconciliation->id}",
            [
                'confirmed_action' => $confirmation->actionKey,
                'confirmation_phrase' => $confirmation->phrase,
                'confirmed_by_user_id' => auth()->id(),
                'confirmed_at' => now()->toIso8601String(),
            ]
        );

        return back()->with('success', 'Reconciliation removed.');
    }
}
