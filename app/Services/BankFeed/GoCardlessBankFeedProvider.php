<?php

namespace App\Services\BankFeed;

use App\Services\BankFeed\Exceptions\BankFeedAccessExpired;
use App\Services\BankFeed\Exceptions\BankFeedRateLimited;
use App\Services\BankFeed\Exceptions\BankFeedUnavailable;
use DateTimeInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoCardlessBankFeedProvider implements BankFeedProvider
{
    private const TOKEN_CACHE_KEY = 'gocardless.access_token';

    public function listInstitutions(string $countryCode = 'GB'): array
    {
        $body = $this->call('get', '/institutions/', query: ['country' => $countryCode]);

        return array_map(fn (array $item) => [
            'id' => (string) $item['id'],
            'name' => (string) ($item['name'] ?? $item['id']),
            'bic' => $item['bic'] ?? null,
        ], is_array($body) ? $body : []);
    }

    public function initiateRequisition(string $institutionId, string $reference, string $redirectUri): array
    {
        // First create an end-user agreement for the configured access duration.
        $agreementDays = (int) config('banking.gocardless.agreement_days', 90);
        $agreement = $this->call('post', '/agreements/enduser/', json: [
            'institution_id' => $institutionId,
            'max_historical_days' => 90,
            'access_valid_for_days' => $agreementDays,
            'access_scope' => ['balances', 'details', 'transactions'],
        ]);

        $requisition = $this->call('post', '/requisitions/', json: [
            'redirect' => $redirectUri,
            'institution_id' => $institutionId,
            'reference' => $reference,
            'agreement' => $agreement['id'] ?? null,
            'user_language' => 'EN',
        ]);

        return [
            'id' => (string) $requisition['id'],
            'link' => (string) $requisition['link'],
            'agreement_id' => $agreement['id'] ?? null,
        ];
    }

    public function finalize(string $requisitionId): array
    {
        $payload = $this->call('get', "/requisitions/{$requisitionId}/");

        $expires = null;
        if (! empty($payload['accounts']) && ($agreementId = $payload['agreement'] ?? null)) {
            try {
                $agreement = $this->call('get', "/agreements/enduser/{$agreementId}/");
                if (! empty($agreement['accepted'])) {
                    $expires = Carbon::parse($agreement['accepted'])
                        ->addDays((int) ($agreement['access_valid_for_days'] ?? config('banking.gocardless.agreement_days', 90)));
                }
            } catch (\Throwable $e) {
                Log::warning('GoCardless: failed to fetch agreement', ['err' => $e->getMessage()]);
            }
        }

        return [
            'account_ids' => array_values(array_map('strval', (array) ($payload['accounts'] ?? []))),
            'agreement_id' => $payload['agreement'] ?? null,
            'expires_at' => $expires,
            'status' => (string) ($payload['status'] ?? 'unknown'),
        ];
    }

    public function pullTransactions(string $accountId, ?DateTimeInterface $since = null): array
    {
        $query = [];
        if ($since) {
            $query['date_from'] = $since->format('Y-m-d');
        }

        $body = $this->call('get', "/accounts/{$accountId}/transactions/", query: $query);

        $rows = [];
        $booked = $body['transactions']['booked'] ?? [];
        foreach ($booked as $tx) {
            $rows[] = [
                'external_id' => (string) ($tx['transactionId'] ?? $tx['internalTransactionId'] ?? hash('sha1', json_encode($tx))),
                'booking_date' => (string) ($tx['bookingDate'] ?? $tx['valueDate'] ?? now()->toDateString()),
                'value_date' => $tx['valueDate'] ?? null,
                'amount' => (float) ($tx['transactionAmount']['amount'] ?? 0),
                'currency' => (string) ($tx['transactionAmount']['currency'] ?? 'GBP'),
                'counterparty_name' => $tx['creditorName'] ?? $tx['debtorName'] ?? null,
                'description' => $tx['remittanceInformationUnstructured'] ?? null,
                'raw' => $tx,
            ];
        }

        return $rows;
    }

    public function revoke(string $requisitionId): void
    {
        try {
            $this->call('delete', "/requisitions/{$requisitionId}/", expectedSuccess: [200, 204]);
        } catch (BankFeedUnavailable $e) {
            // Swallow "already revoked" / 404 — idempotent revoke.
            if (str_contains($e->getMessage(), '404')) {
                return;
            }
            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $json
     * @param  array<int, int>  $expectedSuccess
     * @return array<string, mixed>
     */
    private function call(string $method, string $path, array $query = [], array $json = [], array $expectedSuccess = [200, 201]): array
    {
        $maxAttempts = 4;
        $attempt = 0;
        $backoffMs = 500;

        while (true) {
            $attempt++;
            $request = $this->client();

            if ($query) {
                $request = $request->withQueryParameters($query);
            }

            $response = match (strtolower($method)) {
                'get' => $request->get($path),
                'post' => $request->post($path, $json),
                'delete' => $request->delete($path),
                default => throw new \InvalidArgumentException("Unsupported method {$method}"),
            };

            if (in_array($response->status(), $expectedSuccess, true)) {
                return (array) $response->json();
            }

            if ($response->status() === 401 || $this->isExpiredResponse($response)) {
                Cache::forget(self::TOKEN_CACHE_KEY);
                throw new BankFeedAccessExpired;
            }

            if ($response->status() === 429) {
                $retryAfter = (int) ($response->header('Retry-After') ?: 5);

                if ($attempt >= $maxAttempts) {
                    throw new BankFeedRateLimited(min($retryAfter, 60));
                }

                usleep(min($retryAfter, 60) * 1_000_000);

                continue;
            }

            if ($response->status() >= 500 && $attempt < $maxAttempts) {
                usleep($backoffMs * 1000);
                $backoffMs = min($backoffMs * 2, 8_000);

                continue;
            }

            throw new BankFeedUnavailable(
                "GoCardless {$method} {$path} failed with {$response->status()}: {$response->body()}"
            );
        }
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl((string) config('banking.gocardless.base_url'))
            ->timeout(30)
            ->acceptJson()
            ->withToken($this->accessToken());
    }

    private function accessToken(): string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, now()->addMinutes(50), function (): string {
            $response = Http::baseUrl((string) config('banking.gocardless.base_url'))
                ->timeout(15)
                ->acceptJson()
                ->post('/token/new/', [
                    'secret_id' => (string) config('banking.gocardless.secret_id'),
                    'secret_key' => (string) config('banking.gocardless.secret_key'),
                ]);

            if (! $response->successful()) {
                throw new BankFeedUnavailable("GoCardless token exchange failed: {$response->status()} {$response->body()}");
            }

            $token = $response->json('access');
            if (! is_string($token) || $token === '') {
                throw new BankFeedUnavailable('GoCardless token response missing access field.');
            }

            return $token;
        });
    }

    private function isExpiredResponse(Response $response): bool
    {
        $body = (string) $response->body();

        return str_contains($body, 'EXPIRED') || str_contains($body, 'AccessExpiredError');
    }
}
