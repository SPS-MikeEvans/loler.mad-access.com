<?php

namespace App\Actions;

use App\Exceptions\ClientHasActiveJobsException;
use App\Models\Client;
use App\Models\Job;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteClient
{
    public function __construct(private DeleteInvoice $deleteInvoice) {}

    /**
     * Cascade-soft-delete a client's data, mirroring the original FK on-delete rules:
     *  - inspection_jobs.client_id (restrictOnDelete) → throw if any exist
     *  - invoices.client_id (cascadeOnDelete)         → soft-delete each via DeleteInvoice
     *  - kit_items.client_id (cascadeOnDelete)        → soft-delete each
     *  - kit_groups.client_id (cascadeOnDelete)       → soft-delete each
     *  - users.client_id (nullOnDelete)               → null out the link, keep the user
     *
     * Wrapped in a transaction so a thrown exception rolls back partial work.
     *
     * @throws ClientHasActiveJobsException
     */
    public function cascade(Client $client): void
    {
        if ($client->trashed()) {
            return;
        }

        DB::transaction(function () use ($client): void {
            if (Job::where('client_id', $client->id)->exists()) {
                throw new ClientHasActiveJobsException(
                    'Client has active jobs; complete or delete those first.'
                );
            }

            foreach ($client->invoices as $invoice) {
                $this->deleteInvoice->cascade($invoice);
            }

            foreach ($client->kitItems as $kitItem) {
                $kitItem->delete();
            }

            foreach ($client->kitGroups as $kitGroup) {
                $kitGroup->delete();
            }

            User::where('client_id', $client->id)->update(['client_id' => null]);
        });
    }
}
