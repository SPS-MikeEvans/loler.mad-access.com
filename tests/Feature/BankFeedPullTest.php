<?php

use App\Events\BankConnectionExpired;
use App\Jobs\PullConnectionBankFeed;
use App\Models\BankConnection;
use App\Models\BankTransaction;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    config()->set('banking.gocardless.secret_id', 'test-id');
    config()->set('banking.gocardless.secret_key', 'test-key');
    config()->set('banking.gocardless.base_url', 'https://example.test/api/v2');
});

it('upserts transactions and is idempotent on re-run', function () {
    $connection = BankConnection::factory()->create(['account_ids' => ['acct-1']]);

    Http::fake(function ($req) {
        if (str_contains($req->url(), '/token/new/')) {
            return Http::response(['access' => 'tok_abc'], 200);
        }

        return Http::response([
            'transactions' => [
                'booked' => [
                    [
                        'transactionId' => 'tx-1',
                        'bookingDate' => '2026-05-01',
                        'valueDate' => '2026-05-01',
                        'transactionAmount' => ['amount' => '120.00', 'currency' => 'GBP'],
                        'creditorName' => 'Customer Ltd',
                        'remittanceInformationUnstructured' => 'INV-2026-001',
                    ],
                ],
            ],
        ], 200);
    });

    PullConnectionBankFeed::dispatchSync($connection->id);
    PullConnectionBankFeed::dispatchSync($connection->id);

    expect(BankTransaction::count())->toBe(1);
    $tx = BankTransaction::first();
    expect($tx->external_id)->toBe('tx-1');
    expect((float) $tx->amount)->toBe(120.00);
    expect($connection->fresh()->last_synced_at)->not->toBeNull();
});

it('restores a soft-deleted transaction instead of failing the unique constraint', function () {
    $connection = BankConnection::factory()->create(['account_ids' => ['acct-1']]);

    $tx = BankTransaction::factory()->create([
        'bank_connection_id' => $connection->id,
        'external_id' => 'tx-dup',
    ]);
    $tx->delete();
    expect(BankTransaction::onlyTrashed()->count())->toBe(1);

    Http::fake(function ($req) {
        if (str_contains($req->url(), '/token/new/')) {
            return Http::response(['access' => 'tok_abc'], 200);
        }

        return Http::response([
            'transactions' => [
                'booked' => [
                    [
                        'transactionId' => 'tx-dup',
                        'bookingDate' => '2026-05-01',
                        'transactionAmount' => ['amount' => '50.00', 'currency' => 'GBP'],
                    ],
                ],
            ],
        ], 200);
    });

    PullConnectionBankFeed::dispatchSync($connection->id);

    expect(BankTransaction::count())->toBe(1);
    expect(BankTransaction::onlyTrashed()->count())->toBe(0);
});

it('flags the connection as expired when GoCardless returns AccessExpired', function () {
    Event::fake([BankConnectionExpired::class]);
    $connection = BankConnection::factory()->create(['account_ids' => ['acct-1']]);

    Http::fake(function ($req) {
        if (str_contains($req->url(), '/token/new/')) {
            return Http::response(['access' => 'tok_abc'], 200);
        }

        return Http::response(['summary' => 'AccessExpiredError'], 401);
    });

    PullConnectionBankFeed::dispatchSync($connection->id);

    expect($connection->fresh()->status)->toBe(BankConnection::STATUS_EXPIRED);
    Event::assertDispatched(BankConnectionExpired::class);
});

it('dispatches one job per linked connection from the schedule command', function () {
    BankConnection::factory()->create();
    BankConnection::factory()->create();
    BankConnection::factory()->expired()->create();

    Queue::fake();

    $this->artisan('bank-feed:pull')->assertSuccessful();

    Queue::assertPushed(PullConnectionBankFeed::class, 2);
});
