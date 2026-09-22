<?php

namespace Tests\Support;

use App\Exceptions\DomainConflictException;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

final class Assert
{
    public static function same(mixed $expected, mixed $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            self::fail($message ?: 'Expected '.self::export($expected).', received '.self::export($actual).'.');
        }
    }

    public static function true(bool $condition, string $message = 'Expected condition to be true.'): void
    {
        if (! $condition) {
            self::fail($message);
        }
    }

    public static function databaseCount(string $table, int $expected): void
    {
        self::same($expected, DB::table($table)->count(), "Unexpected row count for {$table}.");
    }

    public static function databaseHas(string $table, array $attributes): void
    {
        if (! DB::table($table)->where($attributes)->exists()) {
            self::fail("No matching row found in {$table}: ".self::export($attributes));
        }
    }

    public static function conflict(string $errorCode, callable $callback): DomainConflictException
    {
        try {
            $callback();
        } catch (DomainConflictException $exception) {
            self::same($errorCode, $exception->errorCode);

            return $exception;
        } catch (Throwable $exception) {
            self::fail('Expected a domain conflict, received '.$exception::class.': '.$exception->getMessage());
        }

        self::fail("Expected domain conflict {$errorCode}, but none was thrown.");
    }

    public static function fail(string $message): never
    {
        throw new RuntimeException($message);
    }

    private static function export(mixed $value): string
    {
        return var_export($value, true);
    }
}
