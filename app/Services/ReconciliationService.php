<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Events\ExpenseReconciled;
use App\Events\InvoicePaid;
use App\Models\AuditLog;
use App\Models\BankTransaction;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Reconciliation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReconciliationService
{
    /**
     * Create a reconciliation matching a bank transaction to an Invoice or Expense.
     * Supports partial matches; the bank tx is only marked reconciled when fully covered.
     * The invoice flips to Paid only when paidAmount() ≥ total_amount.
     */
    public function match(BankTransaction $tx, Invoice|Expense $target, float $matchedAmount, ?string $notes = null): Reconciliation
    {
        return DB::transaction(function () use ($tx, $target, $matchedAmount, $notes) {
            $matchedAmount = round($matchedAmount, 2);

            $reconciliation = Reconciliation::create([
                'bank_transaction_id' => $tx->id,
                'matchable_type' => $target instanceof Invoice ? Reconciliation::TYPE_INVOICE : Reconciliation::TYPE_EXPENSE,
                'matchable_id' => $target->id,
                'matched_amount' => $matchedAmount,
                'matched_by_user_id' => auth()->id(),
                'notes' => $notes,
            ]);

            // Refresh the tx with fresh sum.
            $tx->refresh();
            $totalMatchedOnTx = (float) $tx->reconciliations()->sum('matched_amount');
            if ($totalMatchedOnTx + 0.005 >= abs((float) $tx->amount)) {
                $tx->reconciled_at = now();
                $tx->save();
            }

            if ($target instanceof Invoice) {
                $target->refresh();
                if ($target->isFullyPaid() && $target->status !== InvoiceStatus::Paid) {
                    $target->transitionTo(InvoiceStatus::Paid);
                    $target->paid_at = $target->paid_at ?? now();
                    $target->save();
                    InvoicePaid::dispatch($target);
                }
            } else {
                $target->refresh();
                if ($target->reconciled_at === null) {
                    $target->reconciled_at = now();
                    $target->save();
                }
                ExpenseReconciled::dispatch($target);
            }

            AuditLog::record(
                'reconciled',
                $target instanceof Invoice ? 'Invoice' : 'Expense',
                $target->id,
                sprintf(
                    'Matched £%.2f from bank tx %d',
                    $matchedAmount,
                    $tx->id,
                ),
            );

            return $reconciliation;
        });
    }

    public function unmatch(Reconciliation $reconciliation): void
    {
        DB::transaction(function () use ($reconciliation) {
            /** @var BankTransaction $tx */
            $tx = $reconciliation->bankTransaction;
            /** @var Invoice|Expense $target */
            $target = $reconciliation->matchable;

            $reconciliation->delete();

            if ($tx) {
                $tx->refresh();
                $remaining = (float) $tx->reconciliations()->sum('matched_amount');
                if ($remaining + 0.005 < abs((float) $tx->amount)) {
                    $tx->reconciled_at = null;
                    $tx->save();
                }
            }

            if ($target instanceof Invoice) {
                $target->refresh();
                if (! $target->isFullyPaid() && $target->status === InvoiceStatus::Paid) {
                    $target->status = InvoiceStatus::Sent;
                    $target->paid_at = null;
                    $target->save();
                }
            } elseif ($target instanceof Expense) {
                $target->refresh();
                if ($target->reconciliations()->count() === 0) {
                    $target->reconciled_at = null;
                    $target->save();
                }
            }

            AuditLog::record(
                'unmatched',
                $target instanceof Invoice ? 'Invoice' : 'Expense',
                $target?->id ?? 0,
                "Unmatched reconciliation {$reconciliation->id}",
            );
        });
    }

    /**
     * Suggest invoices and expenses that could match a bank transaction
     * (amount within ±£5, date within ±14 days, not already fully reconciled).
     *
     * @return Collection<int, Model>
     */
    public function suggestMatches(BankTransaction $tx): Collection
    {
        $amount = abs((float) $tx->amount);
        $low = max(0, $amount - 5.0);
        $high = $amount + 5.0;
        $bookingDate = $tx->booking_date;
        $from = $bookingDate->copy()->subDays(14);
        $to = $bookingDate->copy()->addDays(14);

        $suggestions = collect();

        if ($tx->isCredit()) {
            $invoices = Invoice::query()
                ->whereNotIn('status', [InvoiceStatus::Paid->value, InvoiceStatus::Cancelled->value])
                ->whereBetween('issued_date', [$from->toDateString(), $to->toDateString()])
                ->whereBetween('total_amount', [$low, $high])
                ->get()
                ->filter(fn (Invoice $inv) => $inv->outstandingAmount() > 0);

            $suggestions = $suggestions->merge($invoices);
        }

        if ($tx->isDebit()) {
            $expenses = Expense::query()
                ->whereNull('reconciled_at')
                ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
                ->whereBetween('amount', [$low, $high])
                ->get();

            $suggestions = $suggestions->merge($expenses);
        }

        return $suggestions->values();
    }
}
