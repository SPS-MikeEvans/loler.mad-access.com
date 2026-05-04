<?php

use App\Mail\WelcomeClientPortalMail;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

it('resends welcome email to an existing linked client portal user and resets the password', function () {
    Mail::fake();

    $admin = User::factory()->create(['role' => 'admin']);
    $client = Client::factory()->create(['contact_email' => 'linked@example.com']);
    $portalUser = User::factory()->clientViewer()->create([
        'client_id' => $client->id,
        'email' => 'portal-user@example.com',
        'password' => 'old-password',
        'must_change_password' => false,
    ]);

    $this->actingAs($admin)
        ->post(route('clients.resend-welcome', $client))
        ->assertRedirect(route('clients.show', $client));

    $portalUser->refresh();

    expect($portalUser->must_change_password)->toBeTrue();
    expect(Hash::check('old-password', $portalUser->password))->toBeFalse();

    Mail::assertSent(WelcomeClientPortalMail::class, function ($mail) use ($portalUser) {
        return $mail->hasTo($portalUser->email)
            && $mail->purpose === 'reset'
            && Hash::check($mail->temporaryPassword, $portalUser->fresh()->password);
    });

    expect(AuditLog::where('subject_type', 'User')->where('subject_id', $portalUser->id)->where('action', 'updated')->exists())->toBeTrue();
});

it('creates a portal user when resending welcome email for a client without one', function () {
    Mail::fake();

    $inspector = User::factory()->create(['role' => 'inspector']);
    $client = Client::factory()->create([
        'contact_name' => 'Chris Client',
        'contact_email' => 'new-portal@example.com',
    ]);

    $this->actingAs($inspector)
        ->post(route('clients.resend-welcome', $client))
        ->assertRedirect(route('clients.show', $client));

    $portalUser = User::where('email', 'new-portal@example.com')->firstOrFail();

    expect($portalUser->role)->toBe('client_viewer');
    expect($portalUser->client_id)->toBe($client->id);
    expect($portalUser->must_change_password)->toBeTrue();

    Mail::assertSent(WelcomeClientPortalMail::class, fn ($mail) => $mail->hasTo('new-portal@example.com') && $mail->purpose === 'created');
    expect(AuditLog::where('subject_type', 'User')->where('subject_id', $portalUser->id)->where('action', 'created')->exists())->toBeTrue();
});

it('does not resend welcome email when creating a portal user would collide with another user email', function () {
    Mail::fake();

    $admin = User::factory()->create(['role' => 'admin']);
    User::factory()->create(['email' => 'taken@example.com']);
    $client = Client::factory()->create(['contact_email' => 'taken@example.com']);

    $this->actingAs($admin)
        ->post(route('clients.resend-welcome', $client))
        ->assertRedirect(route('clients.show', $client))
        ->assertSessionHas('error');

    expect($client->users()->where('role', 'client_viewer')->exists())->toBeFalse();
    Mail::assertNothingSent();
});
