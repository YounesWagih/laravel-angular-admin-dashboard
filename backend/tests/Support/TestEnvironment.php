<?php

namespace Tests\Support;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class TestEnvironment
{
    public static function boot(): Application
    {
        $application = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $application->make(Kernel::class)->bootstrap();

        $connection = (string) config('database.default');
        $configuration = config("database.connections.{$connection}");
        $testDatabase = (string) (getenv('TEST_DB_DATABASE') ?: env('TEST_DB_DATABASE', ''));

        if (($configuration['driver'] ?? null) !== 'mysql') {
            throw new RuntimeException('The inventory invariant suite requires a MySQL connection.');
        }

        if ($testDatabase === '' || ! preg_match('/^[A-Za-z0-9_]+$/', $testDatabase)) {
            throw new RuntimeException('Set TEST_DB_DATABASE to a pre-created MySQL test database.');
        }

        if (hash_equals((string) $configuration['database'], $testDatabase)) {
            throw new RuntimeException('TEST_DB_DATABASE must differ from the application database.');
        }

        config()->set("database.connections.{$connection}.database", $testDatabase);
        DB::purge($connection);
        DB::setDefaultConnection($connection);

        return $application;
    }

    public static function resetDatabase(): void
    {
        Artisan::call('migrate:fresh', ['--force' => true]);
    }
}
