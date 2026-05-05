<?php

use App\Mail\WelcomeClientPortalMail;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

it('redirects unauthenticated visitors to /login with the email pre-filled', function () {
    $this->get(route('welcome.link', ['email' => 'newclient@example.com']))
        ->assertRedirect(route('login', ['email' => 'newclient@example.com']));

    $this->assertGuest();
});

it('logs out a stale session when the welcome-link email differs', function () {
    $stale = User::factory()->create(['email' => 'someone-else@example.com']);

    $response = $this->actingAs($stale)
        ->get(route('welcome.link', ['email' => 'newclient@example.com']));

    $response->assertRedirect(route('login', ['email' => 'newclient@example.com']));
    $this->assertGuest();
});

it('keeps the session when the welcome-link email matches the logged-in user', function () {
    $user = User::factory()->create(['email' => 'mine@example.com']);

    $this->actingAs($user)
        ->get(route('welcome.link', ['email' => 'mine@example.com']))
        ->assertRedirect();

    $this->assertAuthenticatedAs($user);
});

it('shows the login page with the email field pre-populated when ?email= is present', function () {
    $this->get(route('login', ['email' => 'preset@example.com']))
        ->assertOk()
        ->assertSee('value="preset@example.com"', false);
});

it('uses the welcome-link URL with email param in the welcome email body', function () {
    Mail::fake();

    $admin = User::factory()->create(['role' => 'admin']);
    $client = Client::factory()->create([
        'contact_name' => 'Welcome Test',
        'contact_email' => 'welcome-link@example.com',
    ]);

    $this->actingAs($admin)
        ->post(route('clients.resend-welcome', $client))
        ->assertRedirect();

    Mail::assertSent(WelcomeClientPortalMail::class, function (WelcomeClientPortalMail $mail) {
        $rendered = (string) $mail->render();

        return str_contains($rendered, route('welcome.link', ['email' => 'welcome-link@example.com']));
    });
});

it('forces password change after a resend so an email-exposed password cannot be used long-term', function () {
    Mail::fake();

    $admin = User::factory()->create(['role' => 'admin']);
    $client = Client::factory()->create(['contact_email' => 'force@example.com']);
    $portalUser = User::factory()->clientViewer()->create([
        'client_id' => $client->id,
        'email' => 'force-portal@example.com',
        'must_change_password' => false,
    ]);

    $this->actingAs($admin)
        ->post(route('clients.resend-welcome', $client))
        ->assertRedirect();

    expect($portalUser->refresh()->must_change_password)->toBeTrue();
});
