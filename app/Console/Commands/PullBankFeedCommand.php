<?php

namespace App\Console\Commands;

use App\Jobs\PullConnectionBankFeed;
use App\Models\BankConnection;
use Illuminate\Console\Command;

class PullBankFeedCommand extends Command
{
    protected $signature = 'bank-feed:pull {--connection= : Pull only this connection id} {--sync : Run synchronously instead of dispatching jobs}';

    protected $description = 'Dispatch a PullConnectionBankFeed job per linked, non-expired bank connection.';

    public function handle(): int
    {
        $query = BankConnection::query()->where('status', BankConnection::STATUS_LINKED);

        if ($id = $this->option('connection')) {
            $query->where('id', (int) $id);
        }

        $connections = $query->get();

        if ($connections->isEmpty()) {
            $this->info('No linked bank connections to pull.');

            return self::SUCCESS;
        }

        $sync = (bool) $this->option('sync');

        foreach ($connections as $connection) {
            if ($connection->isExpired()) {
                $this->warn("Connection {$connection->id} is expired — skipping.");

                continue;
            }

            if ($sync) {
                PullConnectionBankFeed::dispatchSync($connection->id);
            } else {
                PullConnectionBankFeed::dispatch($connection->id);
            }

            $this->line("→ Pulled connection {$connection->id} ({$connection->institution_id}).");
        }

        return self::SUCCESS;
    }
}
