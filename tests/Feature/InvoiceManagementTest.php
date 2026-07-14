<?php

use App\Enums\InvoiceStatus;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;

// ─────────────────────────────────────────────────────────────────────
// Authorization
// ─────────────────────────────────────────────────────────────────────

describe('authorization', function () {
    it('redirects guests to login', function () {
        $this->get(route('accounting.invoices.index'))->assertRedirect(route('login'));
    });

    it('forbids inspectors from every route', function () {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $invoice = Invoice::factory()->create();

        $this->actingAs($inspector)->get(route('accounting.invoices.index'))->assertForbidden();
        $this->actingAs($inspector)->get(route('accounting.invoices.edit', $invoice))->assertForbidden();
        $this->actingAs($inspector)->put(route('accounting.invoices.update', $invoice))->assertForbidden();
        $this->actingAs($inspector)->post(route('accounting.invoices.pause-chases', $invoice))->assertForbidden();
        $this->actingAs($inspector)->post(route('accounting.invoices.resume-chases', $invoice))->assertForbidden();
    });

    it('allows admins to view the index', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        Invoice::factory()->create();

        $this->actingAs($admin)->get(route('accounting.invoices.index'))->assertSuccessful();
    });
});

// ─────────────────────────────────────────────────────────────────────
// Index filters & search
// ─────────────────────────────────────────────────────────────────────

describe('index filters', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create(['role' => 'admin']);
    });

    it('filters by status', function () {
        $sent = Invoice::factory()->create();
        $paid = Invoice::factory()->paid()->create();

        $ids = $this->actingAs($this->admin)
            ->get(route('accounting.invoices.index', ['status' => 'paid']))
            ->assertSuccessful()
            ->viewData('invoices')->pluck('id');

        expect($ids)->toContain($paid->id)->not->toContain($sent->id);
    });

    it('excludes paid and cancelled invoices with the unpaid shortcut', function () {
        $sent = Invoice::factory()->create();
        $overdue = Invoice::factory()->overdue()->create();
        $paid = Invoice::factory()->paid()->create();
        $cancelled = Invoice::factory()->cancelled()->create();

        $ids = $this->actingAs($this->admin)
            ->get(route('accounting.invoices.index', ['status' => 'unpaid']))
            ->viewData('invoices')->pluck('id');

        expect($ids)->toContain($sent->id)
            ->toContain($overdue->id)
            ->not->toContain($paid->id)
            ->not->toContain($cancelled->id);
    });

    it('filters by client', function () {
        $mine = Invoice::factory()->create();
        $other = Invoice::factory()->create();

        $ids = $this->actingAs($this->admin)
            ->get(route('accounting.invoices.index', ['client' => $mine->client_id]))
            ->viewData('invoices')->pluck('id');

        expect($ids)->toContain($mine->id)->not->toContain($other->id);
    });

    it('searches by invoice number', function () {
        $match = Invoice::factory()->create(['invoice_number' => 'INV-2026-ZZZFIND']);
        $miss = Invoice::factory()->create(['invoice_number' => 'INV-2026-AAAAAA']);

        $ids = $this->actingAs($this->admin)
            ->get(route('accounting.invoices.index', ['search' => 'ZZZFIND']))
            ->viewData('invoices')->pluck('id');

        expect($ids)->toContain($match->id)->not->toContain($miss->id);
    });

    it('searches by client name', function () {
        $client = Client::factory()->create(['name' => 'Findable Rigging Ltd']);
        $match = Invoice::factory()->create(['client_id' => $client->id]);
        $miss = Invoice::factory()->create();

        $ids = $this->actingAs($this->admin)
            ->get(route('accounting.invoices.index', ['search' => 'Findable Rigging']))
            ->viewData('invoices')->pluck('id');

        expect($ids)->toContain($match->id)->not->toContain($miss->id);
    });

    it('hides invoices cascade-deleted with their client', function () {
        $client = Client::factory()->create(['name' => 'Findable Rigging Ltd']);
        $invoice = Invoice::factory()->create(['client_id' => $client->id]);
        $client->delete();

        $ids = $this->actingAs($this->admin)
            ->get(route('accounting.invoices.index'))
            ->viewData('invoices')->pluck('id');

        expect($ids)->not->toContain($invoice->id);
    });

    it('filters by issued date range', function () {
        $inside = Invoice::factory()->create(['issued_date' => '2026-03-15']);
        $before = Invoice::factory()->create(['issued_date' => '2026-01-01']);
        $after = Invoice::factory()->create(['issued_date' => '2026-06-01']);

        $ids = $this->actingAs($this->admin)
            ->get(route('accounting.invoices.index', ['from' => '2026-03-01', 'to' => '2026-03-31']))
            ->viewData('invoices')->pluck('id');

        expect($ids)->toContain($inside->id)
            ->not->toContain($before->id)
            ->not->toContain($after->id);
    });

    it('paginates 25 per page', function () {
        Invoice::factory()->count(26)->create();

        $invoices = $this->actingAs($this->admin)
            ->get(route('accounting.invoices.index'))
            ->viewData('invoices');

        expect($invoices->count())->toBe(25)
            ->and($invoices->total())->toBe(26);
    });
});

// ─────────────────────────────────────────────────────────────────────
// Edit / update
// ─────────────────────────────────────────────────────────────────────

describe('invoice edit', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create(['role' => 'admin']);
    });

    function validUpdatePayload(array $overrides = []): array
    {
        return array_merge([
            'issued_date' => '2026-06-01',
            'due_date' => '2026-06-15',
            'period_from' => '2026-05-01',
            'period_to' => '2026-05-31',
            'notes' => 'Updated notes',
            'discount_percent' => null,
        ], $overrides);
    }

    it('renders the edit form for an active invoice', function () {
        $invoice = Invoice::factory()->create();

        $this->actingAs($this->admin)
            ->get(route('accounting.invoices.edit', $invoice))
            ->assertSuccessful()
            ->assertSee($invoice->invoice_number);
    });

    it('updates safe fields and writes an audit row', function () {
        $invoice = Invoice::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('accounting.invoices.update', $invoice), validUpdatePayload())
            ->assertRedirect(route('accounting.invoices.index'));

        $invoice->refresh();
        expect($invoice->issued_date->toDateString())->toBe('2026-06-01')
            ->and($invoice->due_date->toDateString())->toBe('2026-06-15')
            ->and($invoice->notes)->toBe('Updated notes');

        $audit = AuditLog::query()
            ->where('subject_type', 'Invoice')
            ->where('subject_id', $invoice->id)
            ->where('action', 'updated')
            ->latest('id')->first();
        expect($audit)->not->toBeNull()
            ->and($audit->user_id)->toBe($this->admin->id);
    });

    it('recomputes the total from the discount', function () {
        $invoice = Invoice::factory()->create(['subtotal' => 100, 'total_amount' => 100]);

        $this->actingAs($this->admin)
            ->put(route('accounting.invoices.update', $invoice), validUpdatePayload(['discount_percent' => 20]));

        expect((float) $invoice->fresh()->total_amount)->toBe(80.00);
    });

    it('resets the total to the subtotal when the discount is cleared', function () {
        $invoice = Invoice::factory()->create([
            'subtotal' => 150,
            'discount_percent' => 10,
            'total_amount' => 135,
        ]);

        $this->actingAs($this->admin)
            ->put(route('accounting.invoices.update', $invoice), validUpdatePayload());

        $invoice->refresh();
        expect($invoice->discount_percent)->toBeNull()
            ->and((float) $invoice->total_amount)->toBe(150.00);
    });

    it('ignores injected non-editable fields', function () {
        $invoice = Invoice::factory()->create(['subtotal' => 100, 'total_amount' => 100]);
        $originalNumber = $invoice->invoice_number;

        $this->actingAs($this->admin)
            ->put(route('accounting.invoices.update', $invoice), validUpdatePayload([
                'subtotal' => 1,
                'total_amount' => 1,
                'status' => 'paid',
                'invoice_number' => 'INV-HACKED',
            ]));

        $invoice->refresh();
        expect((float) $invoice->subtotal)->toBe(100.00)
            ->and((float) $invoice->total_amount)->toBe(100.00)
            ->and($invoice->status)->toBe(InvoiceStatus::Sent)
            ->and($invoice->invoice_number)->toBe($originalNumber);
    });

    it('rejects invalid dates and discounts', function (array $overrides, string $errorField) {
        $invoice = Invoice::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('accounting.invoices.update', $invoice), validUpdatePayload($overrides))
            ->assertSessionHasErrors($errorField);
    })->with([
        'due before issued' => [['due_date' => '2026-05-01'], 'due_date'],
        'period_to before period_from' => [['period_to' => '2026-04-01'], 'period_to'],
        'discount of 100' => [['discount_percent' => 100], 'discount_percent'],
        'negative discount' => [['discount_percent' => -5], 'discount_percent'],
    ]);

    it('blocks editing paid and cancelled invoices', function (string $factoryState) {
        $invoice = Invoice::factory()->{$factoryState}()->create(['notes' => 'original']);

        $this->actingAs($this->admin)
            ->get(route('accounting.invoices.edit', $invoice))
            ->assertRedirect(route('accounting.invoices.index'))
            ->assertSessionHas('error');

        $this->actingAs($this->admin)
            ->put(route('accounting.invoices.update', $invoice), validUpdatePayload())
            ->assertRedirect(route('accounting.invoices.index'))
            ->assertSessionHas('error');

        expect($invoice->fresh()->notes)->toBe('original');
    })->with(['paid', 'cancelled']);
});

// ─────────────────────────────────────────────────────────────────────
// Pause / resume automated chase emails
// ─────────────────────────────────────────────────────────────────────

describe('automated email pause and resume', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create(['role' => 'admin']);
    });

    it('pauses automated emails and writes an audit row', function () {
        $invoice = Invoice::factory()->create();

        $this->actingAs($this->admin)
            ->from(route('accounting.invoices.index'))
            ->post(route('accounting.invoices.pause-chases', $invoice))
            ->assertRedirect(route('accounting.invoices.index'))
            ->assertSessionHas('success');

        expect($invoice->fresh()->chase_emails_paused_at)->not->toBeNull();

        $audit = AuditLog::query()
            ->where('subject_type', 'Invoice')
            ->where('subject_id', $invoice->id)
            ->where('action', 'chases_paused')
            ->first();
        expect($audit)->not->toBeNull()
            ->and($audit->user_id)->toBe($this->admin->id);
    });

    it('resumes automated emails and writes an audit row', function () {
        $invoice = Invoice::factory()->create(['chase_emails_paused_at' => now()]);

        $this->actingAs($this->admin)
            ->post(route('accounting.invoices.resume-chases', $invoice))
            ->assertSessionHas('success');

        expect($invoice->fresh()->chase_emails_paused_at)->toBeNull();
        expect(AuditLog::query()
            ->where('subject_type', 'Invoice')
            ->where('subject_id', $invoice->id)
            ->where('action', 'chases_resumed')
            ->exists())->toBeTrue();
    });

    it('refuses to pause an already-paused invoice', function () {
        $pausedAt = now()->subDay();
        $invoice = Invoice::factory()->create(['chase_emails_paused_at' => $pausedAt]);

        $this->actingAs($this->admin)
            ->post(route('accounting.invoices.pause-chases', $invoice))
            ->assertSessionHas('error');

        expect($invoice->fresh()->chase_emails_paused_at->timestamp)->toBe($pausedAt->timestamp);
    });
});

// ─────────────────────────────────────────────────────────────────────
// Void (cancel) via the index
// ─────────────────────────────────────────────────────────────────────

describe('void via index', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create(['role' => 'admin']);
    });

    it('voids an invoice with the phrase issued by the index page', function () {
        $invoice = Invoice::factory()->create();

        $this->actingAs($this->admin)->get(route('accounting.invoices.index'));

        $this->actingAs($this->admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('clients.invoices.cancel', [$invoice->client, $invoice]), [
                'confirmation_phrase' => "CANCEL-INVOICE-{$invoice->id}",
            ])
            ->assertSessionHasNoErrors();

        expect($invoice->fresh()->status)->toBe(InvoiceStatus::Cancelled);

        $audit = AuditLog::query()
            ->where('subject_type', 'Invoice')
            ->where('subject_id', $invoice->id)
            ->where('action', 'cancelled')
            ->latest('id')->first();
        expect($audit)->not->toBeNull()
            ->and($audit->metadata)->toMatchArray([
                'confirmed_action' => 'cancel.invoice',
                'confirmation_phrase' => "CANCEL-INVOICE-{$invoice->id}",
                'confirmed_by_user_id' => $this->admin->id,
            ]);
    });

    it('rejects the wrong phrase', function () {
        $invoice = Invoice::factory()->create();

        $this->actingAs($this->admin)->get(route('accounting.invoices.index'));

        $this->actingAs($this->admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('clients.invoices.cancel', [$invoice->client, $invoice]), [
                'confirmation_phrase' => 'WRONG',
            ])
            ->assertSessionHasErrors('confirmation_phrase');

        expect($invoice->fresh()->status)->toBe(InvoiceStatus::Sent);
    });

    it('rejects a stale phrase', function () {
        $invoice = Invoice::factory()->create();

        $this->actingAs($this->admin)->get(route('accounting.invoices.index'));
        $this->travel(31)->minutes();

        $this->actingAs($this->admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('clients.invoices.cancel', [$invoice->client, $invoice]), [
                'confirmation_phrase' => "CANCEL-INVOICE-{$invoice->id}",
            ])
            ->assertSessionHasErrors('confirmation_phrase');

        expect($invoice->fresh()->status)->toBe(InvoiceStatus::Sent);
    });

    it('rejects a phrase replayed against a different invoice', function () {
        $client = Client::factory()->create();
        $issuedFor = Invoice::factory()->create(['client_id' => $client->id]);
        $other = Invoice::factory()->create(['client_id' => $client->id]);

        $this->actingAs($this->admin)->get(route('accounting.invoices.index'));

        $this->actingAs($this->admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('clients.invoices.cancel', [$client, $other]), [
                'confirmation_phrase' => "CANCEL-INVOICE-{$issuedFor->id}",
            ])
            ->assertSessionHasErrors('confirmation_phrase');

        expect($other->fresh()->status)->toBe(InvoiceStatus::Sent);
    });

    it('does not issue void confirmations for terminal invoices', function () {
        $paid = Invoice::factory()->paid()->create();
        $cancelled = Invoice::factory()->cancelled()->create();
        $active = Invoice::factory()->create();

        $confirmations = $this->actingAs($this->admin)
            ->get(route('accounting.invoices.index'))
            ->viewData('cancelConfirmations');

        expect($confirmations)->toHaveKey($active->id)
            ->not->toHaveKey($paid->id)
            ->not->toHaveKey($cancelled->id);
    });
});
