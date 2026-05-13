<?php

namespace App\Events;

use App\Models\BankConnection;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BankConnectionExpired
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public BankConnection $bankConnection,
    ) {}
}
