<?php

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\KitItem;
use App\Models\KitType;
use App\Models\User;

function makeClientRecord(string $suffix = 'safety'): Client
{
    return Client::create([
        'name' => 'Safety Client '.$suffix,
        'address' => '1 Safety Street',
        'contact_email' => "safety-{$suffix}@example.test",
        'phone' => '01234567890',
    ]);
}

function makeKitTypeRecord(string $suffix = 'safety'): KitType
{
    return KitType::create([
        'name' => 'Safety Type '.$suffix,
        'interval_months' => 6,
    ]);
}

it('forbids an inspector from deleting a client', function () {
    $inspector = User::factory()->create(['role' => 'inspector']);
    $client = makeClientRecord('forbid-client');

    $this->actingAs($inspector)
        ->delete(route('clients.destroy', $client))
        ->assertForbidden();
});

it('forbids an inspector from deleting a kit item', function () {
    $inspector = User::factory()->create(['role' => 'inspector']);
    $client = makeClientRecord('forbid-item');
    $kitType = makeKitTypeRecord('forbid-item');
    $item = KitItem::factory()->create([
        'client_id' => $client->id,
        'kit_type_id' => $kitType->id,
    ]);

    $this->actingAs($inspector)
        ->delete(route('clients.kit-items.destroy', [$client, $item]))
        ->assertForbidden();
});

it('forbids an inspector from deleting a kit type', function () {
    $inspector = User::factory()->create(['role' => 'inspector']);
    $kitType = makeKitTypeRecord('forbid-type');

    $this->actingAs($inspector)
        ->delete(route('kit-types.destroy', $kitType))
        ->assertForbidden();
});

it('requires a password confirmation session before an admin delete', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = makeClientRecord('password');

    $this->actingAs($admin)->get(route('clients.index'));

    $this->actingAs($admin)
        ->delete(route('clients.destroy', $client), ['confirmation_phrase' => "DELETE-CLIENT-{$client->id}"])
        ->assertRedirect(route('password.confirm'));
});

it('deletes a client after password confirmation and a matching typed phrase', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = makeClientRecord('delete-me');
    $client->update(['name' => 'Delete Me Ltd']);

    $this->actingAs($admin)->get(route('clients.index'));

    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('clients.destroy', $client), ['confirmation_phrase' => "DELETE-CLIENT-{$client->id}"])
        ->assertRedirect(route('clients.index'))
        ->assertSessionHas('success', 'Client Delete Me Ltd deleted.');

    expect(Client::query()->find($client->id))->toBeNull();

    $audit = AuditLog::query()
        ->where('subject_type', 'Client')
        ->where('subject_id', $client->id)
        ->latest('created_at')
        ->first();

    expect($audit)->not->toBeNull();
    expect($audit->metadata)->toMatchArray([
        'confirmed_action' => 'delete.client',
        'confirmation_phrase' => "DELETE-CLIENT-{$client->id}",
        'confirmed_by_user_id' => $admin->id,
    ]);
});

it('rejects a destructive action without an issued confirmation phrase', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = makeClientRecord('missing');

    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('clients.destroy', $client), ['confirmation_phrase' => "DELETE-CLIENT-{$client->id}"])
        ->assertSessionHasErrors('confirmation_phrase');
});

it('rejects a destructive action with the wrong confirmation phrase', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = makeClientRecord('wrong');

    $this->actingAs($admin)->get(route('clients.index'));

    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('clients.destroy', $client), ['confirmation_phrase' => 'DELETE-CLIENT-WRONG'])
        ->assertSessionHasErrors('confirmation_phrase');
});

it('rejects a stale confirmation phrase', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = makeClientRecord('stale');

    $this->actingAs($admin)->get(route('clients.index'));
    $this->travel(31)->minutes();

    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('clients.destroy', $client), ['confirmation_phrase' => "DELETE-CLIENT-{$client->id}"])
        ->assertSessionHasErrors('confirmation_phrase');
});

it('consumes a confirmation phrase after a successful retire action', function () {
    $client = makeClientRecord('retire');
    $user = User::factory()->clientViewer()->create([
        'client_id' => $client->id,
        'email_verified_at' => now(),
    ]);
    $kitType = makeKitTypeRecord('retire');
    $item = KitItem::factory()->create([
        'client_id' => $client->id,
        'kit_type_id' => $kitType->id,
        'status' => 'in_service',
    ]);

    $this->actingAs($user)->get(route('portal.kit.show', $item));

    $this->actingAs($user)
        ->patch(route('portal.kit.retire', $item), ['confirmation_phrase' => "RETIRE-ITEM-{$item->id}"])
        ->assertRedirect(route('portal.kit.index'));

    $this->actingAs($user)
        ->patch(route('portal.kit.retire', $item), ['confirmation_phrase' => "RETIRE-ITEM-{$item->id}"])
        ->assertSessionHasErrors('confirmation_phrase');
});

it('throttles the sixth destructive admin attempt within an hour', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['role' => 'inspector']);

    $this->actingAs($admin)->get(route('users.index'));

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->delete(route('users.destroy', $user), ['confirmation_phrase' => 'WRONG'])
            ->assertSessionHasErrors('confirmation_phrase');
    }

    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('users.destroy', $user), ['confirmation_phrase' => 'WRONG'])
        ->assertStatus(429);
});
