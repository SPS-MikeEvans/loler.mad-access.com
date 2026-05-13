<?php

use App\Events\BankConnectionLinked;
use App\Models\BankConnection;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('banking.gocardless.secret_id', 'test-id');
    config()->set('banking.gocardless.secret_key', 'test-key');
    config()->set('banking.gocardless.base_url', 'https://example.test/api/v2');
    config()->set('banking.gocardless.redirect_uri', 'https://app.test/accounting/bank-connections/oauth/callback');
    config()->set('banking.gocardless.default_institution_id', 'TIDE_TIDEGB22');
});

it('forbids inspectors from listing bank connections', function () {
    $inspector = User::factory()->create(['role' => 'inspector']);
    $this->actingAs($inspector)->get(route('accounting.bank-connections.index'))->assertForbidden();
});

it('initiates a requisition and stores an encrypted requisition_id', function () {
    Event::fake([BankConnectionLinked::class]);
    Http::fake([
        'example.test/api/v2/token/new/' => Http::response(['access' => 'tok_abc', 'access_expires' => 86400], 200),
        'example.test/api/v2/agreements/enduser/' => Http::response(['id' => 'agree-1', 'accepted' => null], 201),
        'example.test/api/v2/requisitions/' => Http::response([
            'id' => 'req-1',
            'link' => 'https://example.test/consent/xyz',
        ], 201),
    ]);

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('accounting.bank-connections.connect'), [
            'institution_id' => 'TIDE_TIDEGB22',
        ])
        ->assertRedirect('https://example.test/consent/xyz');

    $connection = BankConnection::first();
    expect($connection)->not->toBeNull();
    expect($connection->status)->toBe(BankConnection::STATUS_PENDING);
    expect($connection->requisition_id)->toBe('req-1');

    // Encryption-at-rest assertion: the raw column value differs from the plaintext.
    $rawValue = DB::table('bank_connections')->where('id', $connection->id)->value('requisition_id');
    expect($rawValue)->not->toBe('req-1');
    expect($rawValue)->not->toBeNull();
});

it('finalizes a requisition on callback and dispatches BankConnectionLinked', function () {
    Event::fake([BankConnectionLinked::class]);
    $admin = User::factory()->create(['role' => 'admin']);
    $connection = BankConnection::factory()->pending()->create([
        'requisition_id' => 'req-99',
        'requisition_reference' => 'ref-abc',
        'agreement_id' => 'agr-99',
    ]);

    Http::fake([
        'example.test/api/v2/token/new/' => Http::response(['access' => 'tok_abc'], 200),
        'example.test/api/v2/requisitions/req-99/' => Http::response([
            'id' => 'req-99',
            'accounts' => ['acct-1', 'acct-2'],
            'agreement' => 'agr-99',
            'status' => 'LN',
        ], 200),
        'example.test/api/v2/agreements/enduser/agr-99/' => Http::response([
            'id' => 'agr-99',
            'accepted' => now()->toIso8601String(),
            'access_valid_for_days' => 90,
        ], 200),
    ]);

    $this->actingAs($admin)
        ->get(route('accounting.bank-connections.callback', ['ref' => 'ref-abc']))
        ->assertRedirect(route('accounting.bank-connections.index'));

    $connection->refresh();
    expect($connection->status)->toBe(BankConnection::STATUS_LINKED);
    expect($connection->account_ids)->toBe(['acct-1', 'acct-2']);
    expect($connection->linked_at)->not->toBeNull();
    Event::assertDispatched(BankConnectionLinked::class);
});

it('handles a 404 on revoke gracefully', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $connection = BankConnection::factory()->create(['requisition_id' => 'req-gone']);

    Http::fake([
        'example.test/api/v2/token/new/' => Http::response(['access' => 'tok_abc'], 200),
        'example.test/api/v2/requisitions/req-gone/' => Http::response(['detail' => 'Not found'], 404),
    ]);

    // Issue the confirmed action by hitting index first
    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('accounting.bank-connections.index'));

    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('accounting.bank-connections.destroy', $connection), [
            'confirmation_phrase' => "DELETE-BANK-CONNECTION-{$connection->id}",
        ])
        ->assertRedirect(route('accounting.bank-connections.index'));

    expect(BankConnection::find($connection->id))->toBeNull();
});
