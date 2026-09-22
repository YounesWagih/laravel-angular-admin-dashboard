<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case Fulfilled = 'fulfilled';
    case Canceled = 'canceled';

    public function canTransitionTo(self $target): bool
    {
        if ($this === $target) {
            return true;
        }

        if ($target === self::Canceled) {
            return ! $this->isTerminal();
        }

        return match ($this) {
            self::Pending => $target === self::Confirmed,
            self::Confirmed => $target === self::Processing,
            self::Processing => $target === self::Fulfilled,
            self::Fulfilled, self::Canceled => false,
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::Fulfilled || $this === self::Canceled;
    }
}
