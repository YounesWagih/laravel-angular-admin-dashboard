<?php

namespace App\Enums;

enum Locale: string
{
    case English = 'en';
    case Arabic = 'ar';

    public static function fromHeader(?string $value): self
    {
        return self::tryFrom(strtolower(trim((string) $value))) ?? self::English;
    }
}
