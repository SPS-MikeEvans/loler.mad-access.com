<?php

namespace App\Jobs;

use App\Events\BankConnectionExpired;
use App\Models\BankConnection;
use App\Models\BankTransaction;
use App\Services\BankFeed\BankFeedProvider;
use App\Services\BankFeed\Exceptions\BankFeedAccessExpired;
use App\Services\BankFeed\Exceptions\BankFeedException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class PullConnectionBankFeed implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        public int $bankConnectionId,
    ) {}

    public function handle(BankFeedProvider $provider): void
    {
        $connection = BankConnection::find($this->bankConnectionId);
        if (! $connection || $connection->needsRelink()) {
            return;
        }

        $accountIds = (array) ($connection->account_ids ?? []);
        $totalUpserted = 0;
        $failures = 0;

        foreach ($accountIds as $accountId) {
            try {
                $rows = $provider->pullTransactions($accountId, $connection->last_synced_at);
            } catch (BankFeedAccessExpired) {
                $connection->update(['status' => BankConnection::STATUS_EXPIRED]);
                BankConnectionExpired::dispatch($connection);

                return;
            } catch (BankFeedException $e) {
                Log::warning('Bank feed pull failed for account', [
                    'connection_id' => $connection->id,
                    'account_id' => $accountId,
                    'err' => $e->getMessage(),
                ]);
                $failures++;

                continue;
            }

            foreach ($rows as $row) {
                $existing = BankTransaction::withTrashed()
                    ->where('bank_connection_id', $connection->id)
                    ->where('external_id', $row['external_id'])
                    ->first();

                if ($existing) {
                    if ($existing->trashed()) {
                        $existing->restore();
                    }
                    $existing->update([
                        'raw_payload' => $row['raw'],
                    ]);
                } else {
                    BankTransaction::create([
                        'bank_connection_id' => $connection->id,
                        'external_id' => $row['external_id'],
                        'booking_date' => $row['booking_date'],
                        'value_date' => $row['value_date'],
                        'amount' => $row['amount'],
                        'currency' => $row['currency'],
                        'counterparty_name' => $row['counterparty_name'],
                        'description' => $row['description'],
                        'raw_payload' => $row['raw'],
                    ]);
                }
                $totalUpserted++;
            }
        }

        $connection->update(['last_synced_at' => Carbon::now()]);

        Log::info('Bank feed sync complete', [
            'connection_id' => $connection->id,
            'upserted' => $totalUpserted,
            'failed_accounts' => $failures,
        ]);
    }
}
