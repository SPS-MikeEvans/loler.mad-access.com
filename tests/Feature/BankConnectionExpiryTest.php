<?php

use App\Events\BankConnectionExpired;
use App\Models\BankConnection;
use Illuminate\Support\Facades\Event;

it('flags linked connections past their expires_at and fires the event', function () {
    Event::fake([BankConnectionExpired::class]);

    $expired = BankConnection::factory()->create([
        'status' => BankConnection::STATUS_LINKED,
        'expires_at' => now()->subDay(),
    ]);
    $live = BankConnection::factory()->create([
        'status' => BankConnection::STATUS_LINKED,
        'expires_at' => now()->addMonth(),
    ]);

    $this->artisan('accounting:check-bank-expiry')->assertSuccessful();

    expect($expired->fresh()->status)->toBe(BankConnection::STATUS_EXPIRED);
    expect($live->fresh()->status)->toBe(BankConnection::STATUS_LINKED);
    Event::assertDispatchedTimes(BankConnectionExpired::class, 1);
});

it('exposes a needsRelink helper for the index view', function () {
    $live = BankConnection::factory()->create(['expires_at' => now()->addMonth()]);
    $expired = BankConnection::factory()->expired()->create();
    $revoked = BankConnection::factory()->create(['status' => BankConnection::STATUS_REVOKED]);

    expect($live->needsRelink())->toBeFalse();
    expect($expired->needsRelink())->toBeTrue();
    expect($revoked->needsRelink())->toBeTrue();
});
