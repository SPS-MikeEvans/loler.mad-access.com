<?php

namespace App\Actions;

use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class CreateClientPortalUser
{
    public function __construct(private readonly SendClientPortalWelcome $welcomeAction) {}

    public function execute(Client $client): ?User
    {
        if (User::where('email', $client->contact_email)->exists()) {
            Log::warning("Portal account skipped for client {$client->id}: email {$client->contact_email} already in use.");

            return null;
        }

        return $this->welcomeAction->execute($client);
    }
}
