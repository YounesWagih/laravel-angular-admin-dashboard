<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\ReservationStatus;
use App\Enums\Status;
use App\Enums\StockMovementType;
use App\Exceptions\DomainConflictException;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\WarehouseInventory;
use App\Repositories\Contracts\InventoryRepository;
use App\Repositories\Contracts\OrderRepository;
use App\Repositories\Contracts\TransactionManager;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

final class OrderService
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly InventoryRepository $inventories,
        private readonly TransactionManager $transactions,
    ) {}

    public function paginate(User $user, array $filters): LengthAwarePaginator
    {
        return $this->orders->paginateVisibleTo($user, $filters, $user->can('orders.read'));
    }

    /** @return array{order: Order, replayed: bool} */
    public function submit(User $user, array $data, string $idempotencyKey): array
    {
        $lines = collect($data['items'])
            ->map(static fn (array $item): array => [
                'product_id' => (int) $item['product_id'],
                'quantity' => (int) $item['quantity'],
            ])
            ->sortBy('product_id')
            ->values()
            ->all();
        $requestHash = hash('sha256', json_encode($lines, JSON_THROW_ON_ERROR));

        try {
            $order = $this->transactions->run(
                fn (): Order => $this->createReservedOrder($user, $lines, $idempotencyKey, $requestHash),
            );
        } catch (UniqueConstraintViolationException $exception) {
            $existingOrder = $this->orders->findByIdempotency($user, $idempotencyKey);

            if (! $existingOrder) {
                throw $exception;
            }

            if (! hash_equals($existingOrder->request_hash, $requestHash)) {
                throw new DomainConflictException(
                    'idempotency_conflict',
                    __('The idempotency key has already been used with a different request.'),
                );
            }

            return ['order' => $this->orders->withDetails($existingOrder), 'replayed' => true];
        }

        return ['order' => $this->orders->withDetails($order), 'replayed' => false];
    }

    public function details(Order $order): Order
    {
        return $this->orders->withDetails($order);
    }

    public function transition(Order $order, User $actor, OrderStatus $target, ?string $reason): Order
    {
        $order = $this->transactions->run(function () use ($order, $actor, $target, $reason): Order {
            $order = $this->orders->findForUpdate($order->id);
            Gate::forUser($actor)->authorize('transition', [$order, $target]);
            $current = $order->status;

            if ($current === $target) {
                return $order;
            }

            if (! $current->canTransitionTo($target)) {
                throw new DomainConflictException(
                    'invalid_order_transition',
                    __('The order cannot transition from :from to :to.', [
                        'from' => $current->value,
                        'to' => $target->value,
                    ]),
                );
            }

            if ($target === OrderStatus::Canceled || $target === OrderStatus::Fulfilled) {
                $this->settleReservations($order, $actor, $target);
            }

            $attributes = ['status' => $target];

            if ($target === OrderStatus::Canceled) {
                $attributes['cancellation_reason'] = $reason;
            }

            $this->orders->update($order, $attributes);
            $this->orders->createHistory($order, [
                'actor_id' => $actor->id,
                'from_status' => $current,
                'to_status' => $target,
                'reason' => $reason,
            ]);

            return $order;
        });

        return $this->orders->withDetails($order);
    }

    private function createReservedOrder(
        User $user,
        array $lines,
        string $idempotencyKey,
        string $requestHash,
    ): Order {
        $order = $this->orders->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Pending,
            'idempotency_key' => $idempotencyKey,
            'request_hash' => $requestHash,
            'subtotal' => '0.00',
        ]);
        $productIds = array_column($lines, 'product_id');
        $products = $this->orders->lockProducts($productIds)->keyBy('id');

        if ($products->count() !== count($productIds)) {
            throw new DomainConflictException('product_unavailable', __('One or more products are unavailable.'));
        }

        $balances = $this->inventories->lockAllocatableForProducts($productIds)->groupBy('product_id');
        $subtotalCents = 0;

        foreach ($lines as $line) {
            /** @var Product $product */
            $product = $products->get($line['product_id']);

            if ($product->status !== Status::Active) {
                throw new DomainConflictException(
                    'product_unavailable',
                    __('Product :product is not available for ordering.', ['product' => $product->id]),
                );
            }

            $allocations = $this->allocate($product, $line['quantity'], $balances->get($product->id, collect()));
            $unitPriceCents = $this->moneyToCents($product->price);
            $lineTotalCents = $unitPriceCents * $line['quantity'];
            $subtotalCents += $lineTotalCents;
            $orderItem = $this->orders->createItem($order, [
                'product_id' => $product->id,
                'product_name' => $product->getTranslations('name'),
                'unit_price' => $this->centsToMoney($unitPriceCents),
                'quantity' => $line['quantity'],
                'line_total' => $this->centsToMoney($lineTotalCents),
            ]);

            foreach ($allocations as [$inventory, $quantity]) {
                $this->inventories->updateBalance($inventory, $inventory->on_hand, $inventory->reserved + $quantity);
                $this->inventories->createReservation($orderItem, $inventory, $quantity);
                $this->inventories->createMovement([
                    'warehouse_inventory_id' => $inventory->id,
                    'actor_id' => $user->id,
                    'order_id' => $order->id,
                    'type' => StockMovementType::Reservation,
                    'on_hand_delta' => 0,
                    'reserved_delta' => $quantity,
                ]);
            }
        }

        $this->orders->update($order, ['subtotal' => $this->centsToMoney($subtotalCents)]);
        $this->orders->createHistory($order, [
            'actor_id' => $user->id,
            'from_status' => null,
            'to_status' => OrderStatus::Pending,
        ]);

        return $order;
    }

    /** @return array<int, array{WarehouseInventory, int}> */
    private function allocate(Product $product, int $quantity, Collection $balances): array
    {
        $remaining = $quantity;
        $allocations = [];

        foreach ($balances as $inventory) {
            $available = $inventory->on_hand - $inventory->reserved;

            if ($available <= 0) {
                continue;
            }

            $allocated = min($available, $remaining);
            $allocations[] = [$inventory, $allocated];
            $remaining -= $allocated;

            if ($remaining === 0) {
                break;
            }
        }

        if ($remaining > 0) {
            throw new DomainConflictException(
                'insufficient_stock',
                __('Product :product does not have enough available stock.', ['product' => $product->id]),
                ['product_id' => $product->id, 'requested' => $quantity, 'available' => $quantity - $remaining],
            );
        }

        return $allocations;
    }

    private function settleReservations(Order $order, User $actor, OrderStatus $target): void
    {
        $reservations = $this->inventories->lockActiveReservations($order);
        $balances = $this->inventories
            ->lockByIds($reservations->pluck('warehouse_inventory_id')->all())
            ->keyBy('id');

        foreach ($reservations as $reservation) {
            /** @var InventoryReservation $reservation */
            /** @var WarehouseInventory $inventory */
            $inventory = $balances->get($reservation->warehouse_inventory_id);
            $fulfilled = $target === OrderStatus::Fulfilled;
            $this->inventories->updateBalance(
                $inventory,
                $inventory->on_hand - ($fulfilled ? $reservation->quantity : 0),
                $inventory->reserved - $reservation->quantity,
            );
            $this->inventories->updateReservationStatus(
                $reservation,
                ($fulfilled ? ReservationStatus::Consumed : ReservationStatus::Released)->value,
            );
            $this->inventories->createMovement([
                'warehouse_inventory_id' => $inventory->id,
                'actor_id' => $actor->id,
                'order_id' => $order->id,
                'type' => $fulfilled ? StockMovementType::Fulfillment : StockMovementType::ReservationRelease,
                'on_hand_delta' => $fulfilled ? -$reservation->quantity : 0,
                'reserved_delta' => -$reservation->quantity,
            ]);
        }
    }

    private function moneyToCents(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '0');

        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    private function centsToMoney(int $cents): string
    {
        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }
}
