<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';

    /**
     * @return array<int, self>
     */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Draft => [self::Sent, self::Cancelled],
            self::Sent => [self::Paid, self::Overdue, self::Cancelled],
            self::Overdue => [self::Paid, self::Cancelled, self::Sent],
            self::Paid, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        if ($this === $next) {
            return true;
        }

        return in_array($next, $this->allowedNext(), true);
    }

    public function isTerminal(): bool
    {
        return $this === self::Paid || $this === self::Cancelled;
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sent => 'Sent',
            self::Paid => 'Paid',
            self::Overdue => 'Overdue',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Draft => 'bg-gray-100 text-gray-800',
            self::Sent => 'bg-blue-100 text-blue-800',
            self::Paid => 'bg-green-100 text-green-800',
            self::Overdue => 'bg-red-100 text-red-800',
            self::Cancelled => 'bg-yellow-100 text-yellow-800',
        };
    }
}
