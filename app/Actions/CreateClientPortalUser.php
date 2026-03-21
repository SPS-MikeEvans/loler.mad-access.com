<?php

namespace App\Actions;

use App\Mail\WelcomeClientPortalMail;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CreateClientPortalUser
{
    public function execute(Client $client): ?User
    {
        if (User::where('email', $client->contact_email)->exists()) {
            Log::warning("Portal account skipped for client {$client->id}: email {$client->contact_email} already in use.");

            return null;
        }

        $temporaryPassword = Str::password(16);

        $user = User::create([
            'client_id' => $client->id,
            'name' => $client->contact_name,
            'email' => $client->contact_email,
            'password' => $temporaryPassword,
            'role' => 'client_viewer',
            'must_change_password' => true,
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        Mail::to($user->email)->send(new WelcomeClientPortalMail($client, $user, $temporaryPassword));

        AuditLog::record('created', 'User', $user->id, "Portal account created for client {$client->name}");

        return $user;
    }
}
