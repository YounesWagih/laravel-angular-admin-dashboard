<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PermissionName;
use App\Enums\ReservationStatus;
use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseInventory;
use App\Services\InventoryService;
use App\Services\OrderService;
use App\Services\WarehouseService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Schema;
use Tests\Support\Assert;
use Tests\Support\Http;

final class InventoryOrderInvariantsTest
{
    /** @return array<string, callable(): void> */
    public static function cases(): array
    {
        return [
            'migration removes legacy product stock' => self::migrationRemovesLegacyStock(...),
            'product stock is a read-only aggregate' => self::productStockIsReadOnlyAggregate(...),
            'products with inventory cannot be deleted' => self::productWithInventoryCannotBeDeleted(...),
            'products with order history cannot be deleted' => self::productWithOrderHistoryCannotBeDeleted(...),
            'unreferenced products can be deleted' => self::unreferencedProductCanBeDeleted(...),
            'adjustments and transfers conserve stock and write audit movements' => self::adjustmentsAndTransfers(...),
            'adjustment cannot consume reserved stock' => self::adjustmentProtectsReservations(...),
            'maximum inventory transfer fits movements and capacity is enforced' => self::inventoryQuantityBoundaries(...),
            'order splits by priority and idempotent replay has no side effects' => self::splitAllocationAndReplay(...),
            'maximum valid order totals fit persisted decimals' => self::maximumOrderTotals(...),
            'changed idempotent payload returns a conflict' => self::changedIdempotentPayloadConflicts(...),
            'insufficient stock rolls back the complete order' => self::insufficientStockRollsBack(...),
            'fulfillment consumes reservations exactly once' => self::fulfillmentConsumesExactlyOnce(...),
            'cancellation releases reservations exactly once' => self::cancellationReleasesExactlyOnce(...),
            'customer cannot cancel an order confirmed after it was loaded' => self::staleCustomerCancellationIsDenied(...),
            'API conceals customer orders and enforces management permissions' => self::apiAuthorization(...),
            'inactive warehouses are skipped and used warehouses cannot be deleted' => self::inactiveWarehouseAndDeletion(...),
            'competing orders cannot oversell the final unit' => self::concurrentOrdersCannotOversell(...),
            'concurrent matching idempotency keys create one order' => self::concurrentIdempotency(...),
        ];
    }

    private static function migrationRemovesLegacyStock(): void
    {
        Assert::same(false, Schema::hasColumn('products', 'stock'));
        Assert::same(true, Schema::hasTable('warehouse_inventories'));
    }

    private static function productStockIsReadOnlyAggregate(): void
    {
        $admin = User::factory()->create(['type' => UserType::Admin]);
        $product = Product::factory()->create(['status' => Status::Active]);
        WarehouseInventory::factory()->create([
            'product_id' => $product->id,
            'on_hand' => 10,
            'reserved' => 3,
        ]);

        $response = Http::json('GET', "/api/v1/products/{$product->id}", user: $admin);
        $update = Http::json('PATCH', "/api/v1/products/{$product->id}", [
            'name_en' => 'Updated Product',
            'name_ar' => 'منتج محدث',
            'description_en' => null,
            'description_ar' => null,
            'category_id' => $product->category_id,
            'price' => '20.00',
            'stock' => 50,
            'status' => Status::Active->value,
        ], $admin);

        Assert::same(200, $response->getStatusCode());
        Assert::same(7, Http::decoded($response)['data']['stock']);
        Assert::same(422, $update->getStatusCode());
        Assert::true(isset(Http::decoded($update)['errors']['stock']));
    }

    private static function productWithInventoryCannotBeDeleted(): void
    {
        $admin = User::factory()->create(['type' => UserType::Admin]);
        $product = Product::factory()->create();
        $inventory = WarehouseInventory::factory()->create(['product_id' => $product->id]);

        $response = Http::json('DELETE', "/api/v1/products/{$product->id}", user: $admin);

        Assert::same(409, $response->getStatusCode());
        Assert::same('product_in_use', Http::decoded($response)['error']);
        Assert::databaseHas('products', ['id' => $product->id]);
        Assert::databaseHas('warehouse_inventories', ['id' => $inventory->id, 'product_id' => $product->id]);
    }

    private static function productWithOrderHistoryCannotBeDeleted(): void
    {
        $admin = User::factory()->create(['type' => UserType::Admin]);
        $product = Product::factory()->create();
        $item = OrderItem::factory()->create(['product_id' => $product->id]);

        $response = Http::json('DELETE', "/api/v1/products/{$product->id}", user: $admin);

        Assert::same(409, $response->getStatusCode());
        Assert::same('product_in_use', Http::decoded($response)['error']);
        Assert::databaseHas('products', ['id' => $product->id]);
        Assert::databaseHas('order_items', ['id' => $item->id, 'product_id' => $product->id]);
    }

    private static function unreferencedProductCanBeDeleted(): void
    {
        $admin = User::factory()->create(['type' => UserType::Admin]);
        $product = Product::factory()->create();

        $response = Http::json('DELETE', "/api/v1/products/{$product->id}", user: $admin);

        Assert::same(204, $response->getStatusCode());
        Assert::same(false, Product::query()->whereKey($product->id)->exists());
    }

    private static function adjustmentsAndTransfers(): void
    {
        $actor = User::factory()->create(['type' => UserType::Admin]);
        $product = Product::factory()->create(['status' => Status::Active]);
        $source = Warehouse::factory()->create(['priority' => 10]);
        $destination = Warehouse::factory()->create(['priority' => 20]);
        $service = app(InventoryService::class);

        $service->adjust($actor, [
            'warehouse_id' => $source->id,
            'product_id' => $product->id,
            'quantity_delta' => 10,
            'reason' => 'Opening count',
        ]);
        $result = $service->transfer($actor, [
            'from_warehouse_id' => $source->id,
            'to_warehouse_id' => $destination->id,
            'product_id' => $product->id,
            'quantity' => 4,
            'reason' => 'Rebalance',
        ]);

        Assert::databaseHas('warehouse_inventories', ['warehouse_id' => $source->id, 'on_hand' => 6, 'reserved' => 0]);
        Assert::databaseHas('warehouse_inventories', ['warehouse_id' => $destination->id, 'on_hand' => 4, 'reserved' => 0]);
        Assert::databaseCount('stock_movements', 3);
        Assert::same($result['correlation_id'], StockMovement::query()->whereNotNull('correlation_id')->value('correlation_id'));
        Assert::same(10, (int) WarehouseInventory::query()->sum('on_hand'));
        Assert::conflict('insufficient_available_stock', fn () => $service->transfer($actor, [
            'from_warehouse_id' => $source->id,
            'to_warehouse_id' => $destination->id,
            'product_id' => $product->id,
            'quantity' => 7,
            'reason' => 'Excess transfer',
        ]));
        Assert::databaseCount('stock_movements', 3);
    }

    private static function adjustmentProtectsReservations(): void
    {
        $actor = User::factory()->create();
        $inventory = WarehouseInventory::factory()->create(['on_hand' => 10, 'reserved' => 4]);

        Assert::conflict('reserved_stock_conflict', fn () => app(InventoryService::class)->adjust($actor, [
            'warehouse_id' => $inventory->warehouse_id,
            'product_id' => $inventory->product_id,
            'quantity_delta' => -7,
            'reason' => 'Damaged units',
        ]));
        Assert::databaseHas('warehouse_inventories', ['id' => $inventory->id, 'on_hand' => 10, 'reserved' => 4]);
        Assert::databaseCount('stock_movements', 0);
    }

    private static function inventoryQuantityBoundaries(): void
    {
        $admin = User::factory()->create(['type' => UserType::Admin]);
        $product = Product::factory()->create(['status' => Status::Active]);
        $source = Warehouse::factory()->create();
        $destination = Warehouse::factory()->create();
        $extra = Warehouse::factory()->create();
        $sourceBalance = WarehouseInventory::factory()->create([
            'warehouse_id' => $source->id,
            'product_id' => $product->id,
            'on_hand' => WarehouseInventory::MAX_QUANTITY,
        ]);
        $destinationBalance = WarehouseInventory::factory()->create([
            'warehouse_id' => $destination->id,
            'product_id' => $product->id,
            'on_hand' => 0,
        ]);
        $extraBalance = WarehouseInventory::factory()->create([
            'warehouse_id' => $extra->id,
            'product_id' => $product->id,
            'on_hand' => 1,
        ]);

        $transfer = Http::json('POST', '/api/v1/inventory-transfers', [
            'from_warehouse_id' => $source->id,
            'to_warehouse_id' => $destination->id,
            'product_id' => $product->id,
            'quantity' => WarehouseInventory::MAX_QUANTITY,
            'reason' => 'Maximum transfer',
        ], $admin);

        Assert::same(200, $transfer->getStatusCode());
        Assert::databaseHas('warehouse_inventories', ['id' => $sourceBalance->id, 'on_hand' => 0]);
        Assert::databaseHas('warehouse_inventories', ['id' => $destinationBalance->id, 'on_hand' => WarehouseInventory::MAX_QUANTITY]);
        Assert::databaseHas('stock_movements', ['warehouse_inventory_id' => $sourceBalance->id, 'on_hand_delta' => -WarehouseInventory::MAX_QUANTITY]);
        Assert::databaseHas('stock_movements', ['warehouse_inventory_id' => $destinationBalance->id, 'on_hand_delta' => WarehouseInventory::MAX_QUANTITY]);

        $overflowingTransfer = Http::json('POST', '/api/v1/inventory-transfers', [
            'from_warehouse_id' => $extra->id,
            'to_warehouse_id' => $destination->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'reason' => 'Overflowing transfer',
        ], $admin);
        $overflowingAdjustment = Http::json('POST', '/api/v1/inventory-adjustments', [
            'warehouse_id' => $destination->id,
            'product_id' => $product->id,
            'quantity_delta' => 1,
            'reason' => 'Overflowing adjustment',
        ], $admin);

        Assert::same(409, $overflowingTransfer->getStatusCode());
        Assert::same('inventory_capacity_exceeded', Http::decoded($overflowingTransfer)['error']);
        Assert::same(409, $overflowingAdjustment->getStatusCode());
        Assert::same('inventory_capacity_exceeded', Http::decoded($overflowingAdjustment)['error']);
        Assert::databaseHas('warehouse_inventories', ['id' => $destinationBalance->id, 'on_hand' => WarehouseInventory::MAX_QUANTITY]);
        Assert::databaseHas('warehouse_inventories', ['id' => $extraBalance->id, 'on_hand' => 1]);
        Assert::databaseCount('stock_movements', 2);
    }

    private static function splitAllocationAndReplay(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->create([
            'name' => ['en' => 'Priority Product', 'ar' => 'منتج الأولوية'],
            'price' => '12.50',
            'status' => Status::Active,
        ]);
        $first = Warehouse::factory()->create(['priority' => 10]);
        $second = Warehouse::factory()->create(['priority' => 20]);
        $firstBalance = WarehouseInventory::factory()->create(['warehouse_id' => $first->id, 'product_id' => $product->id, 'on_hand' => 2]);
        $secondBalance = WarehouseInventory::factory()->create(['warehouse_id' => $second->id, 'product_id' => $product->id, 'on_hand' => 5]);
        $service = app(OrderService::class);
        $payload = ['items' => [['product_id' => $product->id, 'quantity' => 5]]];

        $created = $service->submit($customer, $payload, 'split-order-1');
        $replayed = $service->submit($customer, $payload, 'split-order-1');

        Assert::same(false, $created['replayed']);
        Assert::same(true, $replayed['replayed']);
        Assert::same($created['order']->id, $replayed['order']->id);
        Assert::same('62.50', $created['order']->subtotal);
        Assert::databaseHas('order_items', ['order_id' => $created['order']->id, 'quantity' => 5, 'line_total' => '62.50']);
        Assert::databaseHas('inventory_reservations', ['warehouse_inventory_id' => $firstBalance->id, 'quantity' => 2]);
        Assert::databaseHas('inventory_reservations', ['warehouse_inventory_id' => $secondBalance->id, 'quantity' => 3]);
        Assert::databaseCount('orders', 1);
        Assert::databaseCount('stock_movements', 2);
    }

    private static function maximumOrderTotals(): void
    {
        $customer = User::factory()->create();
        $category = Category::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $items = [];

        for ($index = 0; $index < 50; $index++) {
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'price' => '9999999999.99',
                'status' => Status::Active,
            ]);
            WarehouseInventory::factory()->create([
                'warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
                'on_hand' => 10000,
            ]);
            $items[] = ['product_id' => $product->id, 'quantity' => 10000];
        }

        $response = Http::json('POST', '/api/v1/orders', ['items' => $items], $customer, [
            'Idempotency-Key' => 'maximum-order-totals',
        ]);

        Assert::same(201, $response->getStatusCode());
        $order = Http::decoded($response)['data'];
        Assert::same('4999999999995000.00', $order['subtotal']);
        Assert::same(50, count($order['items']));
        Assert::databaseHas('orders', ['id' => $order['id'], 'subtotal' => '4999999999995000.00']);
        Assert::databaseHas('order_items', ['order_id' => $order['id'], 'line_total' => '99999999999900.00']);
        Assert::same(50, OrderItem::query()->where('order_id', $order['id'])->count());
    }

    private static function changedIdempotentPayloadConflicts(): void
    {
        [$customer, $product] = self::stockedProduct(5);
        $service = app(OrderService::class);
        $service->submit($customer, ['items' => [['product_id' => $product->id, 'quantity' => 1]]], 'same-key');

        Assert::conflict('idempotency_conflict', fn () => $service->submit(
            $customer,
            ['items' => [['product_id' => $product->id, 'quantity' => 2]]],
            'same-key',
        ));
        Assert::databaseCount('orders', 1);
        Assert::same(1, WarehouseInventory::query()->value('reserved'));
    }

    private static function insufficientStockRollsBack(): void
    {
        $customer = User::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $available = Product::factory()->create(['status' => Status::Active]);
        $short = Product::factory()->create(['status' => Status::Active]);
        WarehouseInventory::factory()->create(['warehouse_id' => $warehouse->id, 'product_id' => $available->id, 'on_hand' => 5]);
        WarehouseInventory::factory()->create(['warehouse_id' => $warehouse->id, 'product_id' => $short->id, 'on_hand' => 1]);

        Assert::conflict('insufficient_stock', fn () => app(OrderService::class)->submit($customer, [
            'items' => [
                ['product_id' => $available->id, 'quantity' => 2],
                ['product_id' => $short->id, 'quantity' => 2],
            ],
        ], 'rollback-order'));
        Assert::databaseCount('orders', 0);
        Assert::databaseCount('order_items', 0);
        Assert::databaseCount('inventory_reservations', 0);
        Assert::databaseCount('stock_movements', 0);
        Assert::same(0, WarehouseInventory::query()->sum('reserved'));
    }

    private static function fulfillmentConsumesExactlyOnce(): void
    {
        [$customer, $product, $inventory] = self::stockedProduct(5, true);
        $manager = User::factory()->create(['type' => UserType::Admin]);
        $service = app(OrderService::class);
        $order = $service->submit($customer, ['items' => [['product_id' => $product->id, 'quantity' => 2]]], 'fulfill-order')['order'];

        Assert::conflict('invalid_order_transition', fn () => $service->transition($order, $manager, OrderStatus::Processing, null));
        $order = $service->transition($order, $manager, OrderStatus::Confirmed, null);
        $order = $service->transition($order, $manager, OrderStatus::Processing, null);
        $order = $service->transition($order, $manager, OrderStatus::Fulfilled, null);
        $service->transition($order, $manager, OrderStatus::Fulfilled, null);

        Assert::databaseHas('warehouse_inventories', ['id' => $inventory->id, 'on_hand' => 3, 'reserved' => 0]);
        Assert::databaseHas('inventory_reservations', ['status' => ReservationStatus::Consumed->value]);
        Assert::databaseCount('order_status_histories', 4);
        Assert::databaseCount('stock_movements', 2);
        Assert::conflict('invalid_order_transition', fn () => $service->transition($order, $manager, OrderStatus::Canceled, null));
    }

    private static function cancellationReleasesExactlyOnce(): void
    {
        [$customer, $product, $inventory] = self::stockedProduct(5, true);
        $manager = User::factory()->create(['type' => UserType::Admin]);
        $service = app(OrderService::class);
        $order = $service->submit($customer, ['items' => [['product_id' => $product->id, 'quantity' => 2]]], 'cancel-order')['order'];

        $order = $service->transition($order, $customer, OrderStatus::Canceled, 'Changed mind');
        $service->transition($order, $manager, OrderStatus::Canceled, 'Changed mind');

        Assert::databaseHas('warehouse_inventories', ['id' => $inventory->id, 'on_hand' => 5, 'reserved' => 0]);
        Assert::databaseHas('inventory_reservations', ['status' => ReservationStatus::Released->value]);
        Assert::databaseCount('order_status_histories', 2);
        Assert::databaseCount('stock_movements', 2);
    }

    private static function staleCustomerCancellationIsDenied(): void
    {
        $customer = User::factory()->create();
        $manager = User::factory()->create(['type' => UserType::Admin]);
        $order = Order::factory()->for($customer)->create();
        $service = app(OrderService::class);

        $service->transition($order, $manager, OrderStatus::Confirmed, null);

        $denied = false;

        try {
            $service->transition($order, $customer, OrderStatus::Canceled, 'Changed mind');
        } catch (AuthorizationException) {
            $denied = true;
        }

        Assert::true($denied, 'Expected the stale customer cancellation to be denied.');
        Assert::databaseHas('orders', ['id' => $order->id, 'status' => OrderStatus::Confirmed->value]);
        Assert::databaseCount('order_status_histories', 1);
        Assert::databaseCount('stock_movements', 0);
    }

    private static function apiAuthorization(): void
    {
        $owner = User::factory()->create();
        $otherCustomer = User::factory()->create();
        $order = Order::factory()->for($owner)->create();
        Warehouse::factory()->create();

        Assert::same(200, Http::json('GET', "/api/v1/orders/{$order->id}", user: $owner)->getStatusCode());
        Assert::same(404, Http::json('GET', "/api/v1/orders/{$order->id}", user: $otherCustomer)->getStatusCode());
        Assert::same(403, Http::json('GET', '/api/v1/warehouses', user: $otherCustomer)->getStatusCode());

        $permission = Permission::findOrCreate(PermissionName::WarehousesRead, 'web');
        $role = Role::findOrCreate('warehouse-reader', 'web');
        $role->givePermissionTo($permission);
        $otherCustomer->syncRoles([$role]);

        Assert::same(200, Http::json('GET', '/api/v1/warehouses', user: $otherCustomer)->getStatusCode());
        Assert::same(403, Http::json('PATCH', "/api/v1/orders/{$order->id}/status", [
            'status' => OrderStatus::Confirmed->value,
        ], $owner)->getStatusCode());
        Assert::same(200, Http::json('PATCH', "/api/v1/orders/{$order->id}/status", [
            'status' => OrderStatus::Canceled->value,
            'reason' => 'No longer needed',
        ], $owner)->getStatusCode());
    }

    private static function inactiveWarehouseAndDeletion(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->create(['status' => Status::Active]);
        $inactive = Warehouse::factory()->inactive()->create(['priority' => 1]);
        $active = Warehouse::factory()->create(['priority' => 100]);
        WarehouseInventory::factory()->create(['warehouse_id' => $inactive->id, 'product_id' => $product->id, 'on_hand' => 10]);
        $activeBalance = WarehouseInventory::factory()->create(['warehouse_id' => $active->id, 'product_id' => $product->id, 'on_hand' => 3]);
        $order = app(OrderService::class)->submit($customer, [
            'items' => [['product_id' => $product->id, 'quantity' => 3]],
        ], 'active-only')['order'];
        $empty = Warehouse::factory()->create();

        Assert::databaseHas('inventory_reservations', ['warehouse_inventory_id' => $activeBalance->id, 'quantity' => 3]);
        Assert::conflict('warehouse_in_use', fn () => app(WarehouseService::class)->delete($inactive));
        app(WarehouseService::class)->delete($empty);
        Assert::same(false, Warehouse::query()->whereKey($empty->id)->exists());
        Assert::same(OrderStatus::Pending, $order->status);
    }

    private static function concurrentOrdersCannotOversell(): void
    {
        [$firstCustomer, $product, $inventory] = self::stockedProduct(1, true);
        $secondCustomer = User::factory()->create();

        $results = self::runWorkers([
            [$firstCustomer->id, $product->id, 1, 'concurrent-a'],
            [$secondCustomer->id, $product->id, 1, 'concurrent-b'],
        ]);

        Assert::same(1, count(array_filter($results, fn (array $result): bool => $result['ok'])));
        Assert::same(1, count(array_filter($results, fn (array $result): bool => ($result['error'] ?? null) === 'insufficient_stock')));
        Assert::databaseCount('orders', 1);
        Assert::databaseHas('warehouse_inventories', ['id' => $inventory->id, 'on_hand' => 1, 'reserved' => 1]);
    }

    private static function concurrentIdempotency(): void
    {
        [$customer, $product, $inventory] = self::stockedProduct(2, true);

        $results = self::runWorkers([
            [$customer->id, $product->id, 1, 'one-concurrent-key'],
            [$customer->id, $product->id, 1, 'one-concurrent-key'],
        ]);

        Assert::true($results[0]['ok'] && $results[1]['ok']);
        Assert::same($results[0]['order_id'], $results[1]['order_id']);
        Assert::databaseCount('orders', 1);
        Assert::databaseHas('warehouse_inventories', ['id' => $inventory->id, 'on_hand' => 2, 'reserved' => 1]);
    }

    /** @return array{User, Product, WarehouseInventory?} */
    private static function stockedProduct(int $onHand, bool $withInventory = false): array
    {
        $customer = User::factory()->create();
        $product = Product::factory()->create(['status' => Status::Active]);
        $warehouse = Warehouse::factory()->create();
        $inventory = WarehouseInventory::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'on_hand' => $onHand,
            'reserved' => 0,
        ]);

        return $withInventory ? [$customer, $product, $inventory] : [$customer, $product];
    }

    /** @return array<int, array<string, mixed>> */
    private static function runWorkers(array $arguments): array
    {
        $processes = [];

        foreach ($arguments as $workerArguments) {
            $pipes = [];
            $process = proc_open(
                [PHP_BINARY, dirname(__DIR__).'/Support/order_worker.php', ...array_map('strval', $workerArguments)],
                [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes,
            );

            if (! is_resource($process)) {
                Assert::fail('Unable to start an order concurrency worker.');
            }

            fclose($pipes[0]);
            $processes[] = [$process, $pipes];
        }

        $results = [];

        foreach ($processes as [$process, $pipes]) {
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = proc_close($process);

            if ($exitCode !== 0) {
                Assert::fail('Concurrency worker failed: '.$error);
            }

            $results[] = json_decode($output, true, flags: JSON_THROW_ON_ERROR);
        }

        return $results;
    }
}
