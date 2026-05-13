<?php

namespace App\Exceptions;

use App\Enums\InvoiceStatus;
use RuntimeException;

class InvalidInvoiceTransition extends RuntimeException
{
    public function __construct(
        public readonly InvoiceStatus $from,
        public readonly InvoiceStatus $to,
    ) {
        parent::__construct("Cannot transition invoice from {$from->value} to {$to->value}.");
    }
}
