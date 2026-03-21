<?php

use App\Models\Client;
use App\Models\User;

function makeClientViewer(bool $mustChange = true): User
{
    $client = Client::create([
        'name' => 'Test Client',
        'address' => '1 Test Street',
        'contact_email' => 'portal-user-'.uniqid().'@test.com',
        'phone' => '01234567890',
    ]);

    return User::factory()->clientViewer()->create([
        'client_id' => $client->id,
        'must_change_password' => $mustChange,
        'email_verified_at' => now(),
    ]);
}

it('redirects a client viewer with must_change_password=true from the portal dashboard', function () {
    $user = makeClientViewer(mustChange: true);

    $this->actingAs($user)
        ->get(route('portal.dashboard'))
        ->assertRedirect(route('password.change'));
});

it('redirects a client viewer with must_change_password=true from any portal kit route', function () {
    $user = makeClientViewer(mustChange: true);

    $this->actingAs($user)
        ->get(route('portal.kit.index'))
        ->assertRedirect(route('password.change'));
});

it('allows a client viewer with must_change_password=true through to the change-password page', function () {
    $user = makeClientViewer(mustChange: true);

    $this->actingAs($user)
        ->get(route('password.change'))
        ->assertOk();
});

it('sets must_change_password to false after a successful password change', function () {
    $user = makeClientViewer(mustChange: true);

    $this->actingAs($user)
        ->patch(route('password.change.update'), [
            'password' => 'new-secure-password-123',
            'password_confirmation' => 'new-secure-password-123',
        ])
        ->assertRedirect(route('portal.dashboard'));

    expect($user->fresh()->must_change_password)->toBeFalse();
});

it('does not redirect an admin even if must_change_password is true', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'must_change_password' => true,
    ]);

    // Admin hits the admin dashboard, not the portal — password.changed middleware is not on admin routes
    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk();
});
