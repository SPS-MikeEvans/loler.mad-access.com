<?php

use App\Mail\WelcomeClientPortalMail;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

it('creates a portal user and sends a welcome email when a client is added', function () {
    Mail::fake();

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('clients.store'), [
        'name' => 'Acme Rigging Ltd',
        'contact_name' => 'Jane Doe',
        'address' => '1 High Street, Sheffield',
        'contact_email' => 'jane@acmerigging.co.uk',
        'phone' => '01234567890',
    ])->assertRedirect();

    $client = Client::where('contact_email', 'jane@acmerigging.co.uk')->firstOrFail();

    $portalUser = User::where('email', 'jane@acmerigging.co.uk')->firstOrFail();

    expect($portalUser->role)->toBe('client_viewer');
    expect($portalUser->client_id)->toBe($client->id);
    expect($portalUser->must_change_password)->toBeTrue();
    expect($portalUser->email_verified_at)->not->toBeNull();

    Mail::assertSent(WelcomeClientPortalMail::class, fn ($mail) => $mail->hasTo('jane@acmerigging.co.uk'));
});

it('does not create a second portal user if the contact email already belongs to a user', function () {
    Mail::fake();

    $admin = User::factory()->create(['role' => 'admin']);

    User::factory()->create(['email' => 'existing@example.com']);

    $this->actingAs($admin)->post(route('clients.store'), [
        'name' => 'Existing Client Ltd',
        'contact_name' => 'Bob Smith',
        'address' => '2 Low Street, Leeds',
        'contact_email' => 'existing@example.com',
        'phone' => '09876543210',
    ])->assertRedirect();

    expect(User::where('email', 'existing@example.com')->count())->toBe(1);

    expect(Client::where('contact_email', 'existing@example.com')->exists())->toBeTrue();
});

it('redirects to clients.show after creating a client', function () {
    Mail::fake();

    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->post(route('clients.store'), [
        'name' => 'Redirect Test Ltd',
        'contact_name' => 'Alice',
        'address' => '3 Mid Street, Manchester',
        'contact_email' => 'alice@redirecttest.co.uk',
        'phone' => '01111222333',
    ]);

    $client = Client::where('contact_email', 'alice@redirecttest.co.uk')->firstOrFail();

    $response->assertRedirect(route('clients.show', $client));
});
