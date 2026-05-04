<?php

namespace App\Actions;

use App\Mail\WelcomeClientPortalMail;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendClientPortalWelcome
{
    public function execute(Client $client, bool $allowCreate = true): ?User
    {
        $user = $client->users()
            ->where('role', 'client_viewer')
            ->orderBy('id')
            ->first();

        $created = false;

        if (! $user) {
            if (! $allowCreate || User::where('email', $client->contact_email)->exists()) {
                return null;
            }

            $user = new User([
                'client_id' => $client->id,
                'name' => $client->contact_name,
                'email' => $client->contact_email,
                'role' => 'client_viewer',
            ]);
            $created = true;
        }

        $temporaryPassword = Str::password(16);

        $user->forceFill([
            'client_id' => $client->id,
            'password' => $temporaryPassword,
            'must_change_password' => true,
            'email_verified_at' => now(),
        ])->save();

        Mail::to($user->email)->send(new WelcomeClientPortalMail($client, $user, $temporaryPassword, $created ? 'created' : 'reset'));

        AuditLog::record(
            $created ? 'created' : 'updated',
            'User',
            $user->id,
            $created
                ? "Portal account created for client {$client->name}"
                : "Portal account welcome email resent for client {$client->name}",
            [
                'client_id' => $client->id,
                'portal_user_id' => $user->id,
                'welcome_action' => $created ? 'created' : 'reset',
                'acted_by_user_id' => auth()->id(),
            ]
        );

        return $user;
    }
}
