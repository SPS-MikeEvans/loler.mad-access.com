<?php

use App\Models\Client;
use App\Models\KitBrand;
use App\Models\User;

it('shows the brands index to admins', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('kit-brands.index'))
        ->assertOk()
        ->assertSee('Manage Brands');
});

it('forbids inspectors from viewing brand admin', function () {
    $inspector = User::factory()->create(['role' => 'inspector']);

    $this->actingAs($inspector)
        ->get(route('kit-brands.index'))
        ->assertForbidden();
});

it('lets an admin add a new brand', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('kit-brands.store'), ['name' => 'Heightec'])
        ->assertRedirect(route('kit-brands.index'));

    expect(KitBrand::where('name', 'Heightec')->where('is_active', true)->exists())->toBeTrue();
});

it('rejects duplicate brand names', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    KitBrand::create(['name' => 'Heightec', 'is_active' => true]);

    $this->actingAs($admin)
        ->post(route('kit-brands.store'), ['name' => 'Heightec'])
        ->assertSessionHasErrors('name');
});

it('toggles is_active on update', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $brand = KitBrand::firstOrCreate(['name' => 'Petzl'], ['is_active' => true]);

    $this->actingAs($admin)
        ->patch(route('kit-brands.update', $brand), ['is_active' => '0'])
        ->assertRedirect();

    expect($brand->refresh()->is_active)->toBeFalse();

    $this->actingAs($admin)
        ->patch(route('kit-brands.update', $brand), ['is_active' => '1'])
        ->assertRedirect();

    expect($brand->refresh()->is_active)->toBeTrue();
});

it('deletes a brand after typed-phrase confirmation', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $brand = KitBrand::create(['name' => 'TempBrand', 'is_active' => true]);

    $this->actingAs($admin)->get(route('kit-brands.index'));

    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('kit-brands.destroy', $brand), [
            'confirmation_phrase' => "DELETE-BRAND-{$brand->id}",
        ])
        ->assertRedirect(route('kit-brands.index'));

    expect(KitBrand::find($brand->id))->toBeNull();
});

it('forbids client_viewer users from any brand action', function () {
    $client = Client::create([
        'name' => 'Acme',
        'address' => '1 Acme Way',
        'contact_email' => 'acme@example.test',
        'phone' => '01234567890',
    ]);
    $viewer = User::factory()->clientViewer()->create([
        'client_id' => $client->id,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($viewer)->get(route('kit-brands.index'))->assertForbidden();
    $this->actingAs($viewer)->post(route('kit-brands.store'), ['name' => 'X'])->assertForbidden();
});
