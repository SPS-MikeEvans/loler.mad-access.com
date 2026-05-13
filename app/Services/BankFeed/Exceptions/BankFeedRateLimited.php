<?php

namespace App\Services\BankFeed\Exceptions;

class BankFeedRateLimited extends BankFeedException
{
    public function __construct(public readonly int $retryAfterSeconds = 60)
    {
        parent::__construct("Bank feed provider rate-limited (retry after {$retryAfterSeconds}s).");
    }
}
