<?php

namespace App\Console\Commands;

use App\Events\BankConnectionExpired;
use App\Models\BankConnection;
use Illuminate\Console\Command;

class CheckBankExpiryCommand extends Command
{
    protected $signature = 'accounting:check-bank-expiry';

    protected $description = 'Flag bank connections at or past expiry and fire BankConnectionExpired.';

    public function handle(): int
    {
        $now = now();
        $count = 0;

        BankConnection::query()
            ->where('status', BankConnection::STATUS_LINKED)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', $now)
            ->each(function (BankConnection $connection) use (&$count): void {
                $connection->update(['status' => BankConnection::STATUS_EXPIRED]);
                BankConnectionExpired::dispatch($connection);
                $count++;
            });

        $this->info("Flagged {$count} bank connection(s) as expired.");

        return self::SUCCESS;
    }
}
