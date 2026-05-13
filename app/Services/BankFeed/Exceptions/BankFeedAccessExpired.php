<?php

namespace App\Services\BankFeed\Exceptions;

class BankFeedAccessExpired extends BankFeedException
{
    public function __construct()
    {
        parent::__construct('Bank feed access has expired and needs to be re-linked.');
    }
}
