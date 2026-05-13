<?php

namespace App\Services\BankFeed;

interface BankFeedProvider
{
    /**
     * @return array<int, array{id: string, name: string, bic?: string|null}>
     */
    public function listInstitutions(string $countryCode = 'GB'): array;

    /**
     * @return array{id: string, link: string}
     */
    public function initiateRequisition(string $institutionId, string $reference, string $redirectUri): array;

    /**
     * @return array{account_ids: array<int, string>, agreement_id: ?string, expires_at: ?\DateTimeInterface, status: string}
     */
    public function finalize(string $requisitionId): array;

    /**
     * @return array<int, array{
     *     external_id: string,
     *     booking_date: string,
     *     value_date: ?string,
     *     amount: float,
     *     currency: string,
     *     counterparty_name: ?string,
     *     description: ?string,
     *     raw: array<string, mixed>
     * }>
     */
    public function pullTransactions(string $accountId, ?\DateTimeInterface $since = null): array;

    public function revoke(string $requisitionId): void;
}
