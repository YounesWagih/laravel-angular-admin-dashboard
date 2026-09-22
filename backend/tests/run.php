<?php

use Tests\Feature\InventoryOrderInvariantsTest;
use Tests\Support\TestEnvironment;

require __DIR__.'/bootstrap.php';

try {
    TestEnvironment::boot();
} catch (Throwable $exception) {
    fwrite(STDERR, "Bail out! {$exception->getMessage()}\n");
    exit(1);
}

$tests = InventoryOrderInvariantsTest::cases();
echo "TAP version 13\n1..".count($tests)."\n";
$failures = 0;
$number = 0;

foreach ($tests as $name => $test) {
    $number++;

    try {
        TestEnvironment::resetDatabase();
        $test();
        echo "ok {$number} - {$name}\n";
    } catch (Throwable $exception) {
        $failures++;
        $message = str_replace(["\r", "\n"], [' ', ' '], $exception->getMessage());
        echo "not ok {$number} - {$name}\n  ---\n  message: ".json_encode($message)."\n  ...\n";
    }
}

exit($failures === 0 ? 0 : 1);
