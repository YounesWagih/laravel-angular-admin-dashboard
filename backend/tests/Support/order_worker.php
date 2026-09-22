<?php

use App\Exceptions\DomainConflictException;
use App\Models\User;
use App\Services\OrderService;
use Tests\Support\TestEnvironment;

require dirname(__DIR__).'/bootstrap.php';

try {
    TestEnvironment::boot();
    $user = User::query()->findOrFail((int) $argv[1]);
    $result = app(OrderService::class)->submit($user, [
        'items' => [[
            'product_id' => (int) $argv[2],
            'quantity' => (int) $argv[3],
        ]],
    ], $argv[4]);

    echo json_encode([
        'ok' => true,
        'order_id' => $result['order']->id,
        'replayed' => $result['replayed'],
    ], JSON_THROW_ON_ERROR);
} catch (DomainConflictException $exception) {
    echo json_encode(['ok' => false, 'error' => $exception->errorCode], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception::class.': '.$exception->getMessage());
    exit(1);
}
