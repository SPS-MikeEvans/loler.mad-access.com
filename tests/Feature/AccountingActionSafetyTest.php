<?php

use App\Enums\InvoiceStatus;
use App\Models\AuditLog;
use App\Models\BankConnection;
use App\Models\BankTransaction;
use App\Models\Client;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\Reconciliation;
use App\Models\User;
use App\Services\ReconciliationService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('banking.gocardless.secret_id', 'test-id');
    config()->set('banking.gocardless.secret_key', 'test-key');
    config()->set('banking.gocardless.base_url', 'https://example.test/api/v2');
    Http::fake([
        'example.test/api/v2/token/new/' => Http::response(['access' => 'tok'], 200),
        'example.test/api/v2/*' => Http::response(['detail' => 'Not found'], 404),
    ]);
});

// ─────────────────────────────────────────────────────────────────────
// Expense destroy
// ─────────────────────────────────────────────────────────────────────

describe('expense destroy', function () {
    it('forbids inspectors', function () {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $expense = Expense::factory()->create();
        $this->actingAs($inspector)
            ->delete(route('accounting.expenses.destroy', $expense))
            ->assertForbidden();
    });

    it('requires password confirmation before deletion', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $expense = Expense::factory()->create();
        $this->actingAs($admin)->get(route('accounting.expenses.show', $expense));

        $this->actingAs($admin)
            ->delete(route('accounting.expenses.destroy', $expense), ['confirmation_phrase' => "DELETE-EXPENSE-{$expense->id}"])
            ->assertRedirect(route('password.confirm'));
    });

    it('rejects a missing confirmation phrase', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $expense = Expense::factory()->create();
        $this->actingAs($admin)->get(route('accounting.expenses.show', $expense));

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->delete(route('accounting.expenses.destroy', $expense))
            ->assertSessionHasErrors('confirmation_phrase');
    });

    it('rejects the wrong confirmation phrase', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $expense = Expense::factory()->create();
        $this->actingAs($admin)->get(route('accounting.expenses.show', $expense));

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->delete(route('accounting.expenses.destroy', $expense), ['confirmation_phrase' => 'WRONG'])
            ->assertSessionHasErrors('confirmation_phrase');
    });

    it('rejects a stale confirmation phrase', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $expense = Expense::factory()->create();
        $this->actingAs($admin)->get(route('accounting.expenses.show', $expense));
        $this->travel(31)->minutes();

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->delete(route('accounting.expenses.destroy', $expense), ['confirmation_phrase' => "DELETE-EXPENSE-{$expense->id}"])
            ->assertSessionHasErrors('confirmation_phrase');
    });

    it('rejects a replayed confirmation against a different expense', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $issuedFor = Expense::factory()->create();
        $other = Expense::factory()->create();

        // Issue a phrase for one expense, try to use it against another.
        $this->actingAs($admin)->get(route('accounting.expenses.show', $issuedFor));

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->delete(route('accounting.expenses.destroy', $other), ['confirmation_phrase' => "DELETE-EXPENSE-{$issuedFor->id}"])
            ->assertSessionHasErrors('confirmation_phrase');

        expect(Expense::find($other->id))->not->toBeNull();
    });

    it('writes an audit row with the confirmation metadata and deletes', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $expense = Expense::factory()->create();
        $this->actingAs($admin)->get(route('accounting.expenses.show', $expense));

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->delete(route('accounting.expenses.destroy', $expense), ['confirmation_phrase' => "DELETE-EXPENSE-{$expense->id}"])
            ->assertRedirect(route('accounting.expenses.index'));

        expect(Expense::find($expense->id))->toBeNull();

        $audit = AuditLog::query()
            ->where('subject_type', 'Expense')
            ->where('subject_id', $expense->id)
            ->latest('id')->first();
        expect($audit)->not->toBeNull();
        expect($audit->metadata)->toMatchArray([
            'confirmed_action' => 'delete.expense',
            'confirmation_phrase' => "DELETE-EXPENSE-{$expense->id}",
            'confirmed_by_user_id' => $admin->id,
        ]);
    });
});

// ─────────────────────────────────────────────────────────────────────
// Expense category destroy
// ─────────────────────────────────────────────────────────────────────

describe('expense category destroy', function () {
    it('forbids inspectors', function () {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $cat = ExpenseCategory::factory()->create();
        $this->actingAs($inspector)
            ->delete(route('accounting.expense-categories.destroy', $cat))
            ->assertForbidden();
    });

    it('requires password confirmation', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $cat = ExpenseCategory::factory()->create();
        $this->actingAs($admin)->get(route('accounting.expense-categories.index'));

        $this->actingAs($admin)
            ->delete(route('accounting.expense-categories.destroy', $cat), ['confirmation_phrase' => "DELETE-EXPENSE-CATEGORY-{$cat->id}"])
            ->assertRedirect(route('password.confirm'));
    });

    it('rejects wrong confirmation phrase', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $cat = ExpenseCategory::factory()->create();
        $this->actingAs($admin)->get(route('accounting.expense-categories.index'));

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->delete(route('accounting.expense-categories.destroy', $cat), ['confirmation_phrase' => 'WRONG'])
            ->assertSessionHasErrors('confirmation_phrase');
    });

    it('deletes after correct phrase and writes an audit row', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $cat = ExpenseCategory::factory()->create();
        $this->actingAs($admin)->get(route('accounting.expense-categories.index'));

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->delete(route('accounting.expense-categories.destroy', $cat), ['confirmation_phrase' => "DELETE-EXPENSE-CATEGORY-{$cat->id}"])
            ->assertRedirect();

        expect(ExpenseCategory::find($cat->id))->toBeNull();
        $audit = AuditLog::query()->where('subject_type', 'ExpenseCategory')->where('subject_id', $cat->id)->latest('id')->first();
        expect($audit)->not->toBeNull();
        expect($audit->metadata['confirmed_by_user_id'])->toBe($admin->id);
    });
});

// ─────────────────────────────────────────────────────────────────────
// Bank connection destroy
// ─────────────────────────────────────────────────────────────────────

describe('bank connection destroy', function () {
    it('forbids inspectors', function () {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $conn = BankConnection::factory()->create();
        $this->actingAs($inspector)
            ->delete(route('accounting.bank-connections.destroy', $conn))
            ->assertForbidden();
    });

    it('requires password confirmation', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $conn = BankConnection::factory()->create();
        $this->actingAs($admin)->get(route('accounting.bank-connections.index'));

        $this->actingAs($admin)
            ->delete(route('accounting.bank-connections.destroy', $conn), ['confirmation_phrase' => "DELETE-BANK-CONNECTION-{$conn->id}"])
            ->assertRedirect(route('password.confirm'));
    });

    it('rejects wrong phrase', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $conn = BankConnection::factory()->create();
        $this->actingAs($admin)->get(route('accounting.bank-connections.index'));

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->delete(route('accounting.bank-connections.destroy', $conn), ['confirmation_phrase' => 'NOPE'])
            ->assertSessionHasErrors('confirmation_phrase');
    });

    it('soft-deletes related transactions+reconciliations and audits', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $conn = BankConnection::factory()->create();
        $tx = BankTransaction::factory()->create(['bank_connection_id' => $conn->id]);
        $invoice = Invoice::factory()->create();
        Reconciliation::factory()->create([
            'bank_transaction_id' => $tx->id,
            'matchable_type' => Reconciliation::TYPE_INVOICE,
            'matchable_id' => $invoice->id,
        ]);

        $this->actingAs($admin)->get(route('accounting.bank-connections.index'));

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->delete(route('accounting.bank-connections.destroy', $conn), ['confirmation_phrase' => "DELETE-BANK-CONNECTION-{$conn->id}"])
            ->assertRedirect();

        expect(BankConnection::find($conn->id))->toBeNull();
        expect(BankTransaction::find($tx->id))->toBeNull();
        expect(BankTransaction::withTrashed()->find($tx->id)?->deleted_at)->not->toBeNull();
        $audit = AuditLog::query()->where('subject_type', 'BankConnection')->where('subject_id', $conn->id)->latest('id')->first();
        expect($audit)->not->toBeNull();
        expect($audit->metadata['confirmed_action'])->toBe('delete.bank-connection');
    });
});

// ─────────────────────────────────────────────────────────────────────
// Invoice cancel
// ─────────────────────────────────────────────────────────────────────

describe('invoice cancel', function () {
    it('forbids inspectors', function () {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $client = Client::factory()->create();
        $invoice = Invoice::factory()->create(['client_id' => $client->id]);

        $this->actingAs($inspector)
            ->post(route('clients.invoices.cancel', [$client, $invoice]))
            ->assertForbidden();
    });

    it('requires password confirmation', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = Client::factory()->create();
        $invoice = Invoice::factory()->create(['client_id' => $client->id]);
        $this->actingAs($admin)->get(route('clients.invoices.show', [$client, $invoice]));

        $this->actingAs($admin)
            ->post(route('clients.invoices.cancel', [$client, $invoice]), ['confirmation_phrase' => "CANCEL-INVOICE-{$invoice->id}"])
            ->assertRedirect(route('password.confirm'));
    });

    it('rejects a wrong phrase and keeps the invoice live', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = Client::factory()->create();
        $invoice = Invoice::factory()->create(['client_id' => $client->id]);
        $this->actingAs($admin)->get(route('clients.invoices.show', [$client, $invoice]));

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('clients.invoices.cancel', [$client, $invoice]), ['confirmation_phrase' => 'NOPE'])
            ->assertSessionHasErrors('confirmation_phrase');

        expect($invoice->fresh()->status)->toBe(InvoiceStatus::Sent);
    });

    it('cancels after a correct phrase and writes an audit row', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = Client::factory()->create();
        $invoice = Invoice::factory()->create(['client_id' => $client->id]);
        $this->actingAs($admin)->get(route('clients.invoices.show', [$client, $invoice]));

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('clients.invoices.cancel', [$client, $invoice]), [
                'confirmation_phrase' => "CANCEL-INVOICE-{$invoice->id}",
                'cancellation_reason' => 'Wrong client',
            ])
            ->assertRedirect();

        expect($invoice->fresh()->status)->toBe(InvoiceStatus::Cancelled);
        $audit = AuditLog::query()->where('subject_type', 'Invoice')->where('subject_id', $invoice->id)->where('action', 'cancelled')->latest('id')->first();
        expect($audit)->not->toBeNull();
        expect($audit->metadata['confirmed_action'])->toBe('cancel.invoice');
    });
});

// ─────────────────────────────────────────────────────────────────────
// Reconciliation destroy (unmatch)
// ─────────────────────────────────────────────────────────────────────

describe('reconciliation unmatch', function () {
    it('forbids inspectors', function () {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $tx = BankTransaction::factory()->credit(50)->create();
        $invoice = Invoice::factory()->create(['total_amount' => 100]);
        $rec = Reconciliation::factory()->create([
            'bank_transaction_id' => $tx->id,
            'matchable_type' => Reconciliation::TYPE_INVOICE,
            'matchable_id' => $invoice->id,
        ]);

        $this->actingAs($inspector)
            ->delete(route('accounting.reconciliation.destroy', $rec))
            ->assertForbidden();
    });

    it('requires password confirmation', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $invoice = Invoice::factory()->create(['total_amount' => 100]);
        $tx = BankTransaction::factory()->credit(100)->create();
        $this->actingAs($admin);
        $rec = app(ReconciliationService::class)->match($tx, $invoice, 100.00);

        $this->actingAs($admin)->get(route('accounting.reconciliation.index'));

        $this->actingAs($admin)
            ->delete(route('accounting.reconciliation.destroy', $rec), ['confirmation_phrase' => "UNMATCH-RECONCILIATION-{$rec->id}"])
            ->assertRedirect(route('password.confirm'));
    });

    it('rejects the wrong phrase', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $invoice = Invoice::factory()->create(['total_amount' => 100]);
        $tx = BankTransaction::factory()->credit(100)->create();
        $this->actingAs($admin);
        $rec = app(ReconciliationService::class)->match($tx, $invoice, 100.00);

        $this->actingAs($admin)->get(route('accounting.reconciliation.index'));

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->delete(route('accounting.reconciliation.destroy', $rec), ['confirmation_phrase' => 'NOPE'])
            ->assertSessionHasErrors('confirmation_phrase');
    });

    it('unmatches after the correct phrase and audits', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $invoice = Invoice::factory()->create(['total_amount' => 100]);
        $tx = BankTransaction::factory()->credit(100)->create();
        $this->actingAs($admin);
        $rec = app(ReconciliationService::class)->match($tx, $invoice, 100.00);

        expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid);

        $this->actingAs($admin)->get(route('accounting.reconciliation.index'));

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->delete(route('accounting.reconciliation.destroy', $rec), ['confirmation_phrase' => "UNMATCH-RECONCILIATION-{$rec->id}"])
            ->assertRedirect();

        expect($invoice->fresh()->status)->toBe(InvoiceStatus::Sent);

        $audit = AuditLog::query()->where('subject_type', 'Reconciliation')->where('subject_id', $rec->id)->where('action', 'unmatched')->latest('id')->first();
        expect($audit)->not->toBeNull();
    });
});
