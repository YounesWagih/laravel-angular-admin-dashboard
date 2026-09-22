<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class OrderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Order $order): Response
    {
        return $order->user_id === $user->id || $user->can('orders.read')
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function transition(User $user, Order $order, OrderStatus $target): Response
    {
        if ($user->can('orders.update')) {
            return Response::allow();
        }

        if ($order->user_id !== $user->id) {
            return Response::denyAsNotFound();
        }

        return $order->status === OrderStatus::Pending && $target === OrderStatus::Canceled
            ? Response::allow()
            : Response::deny();
    }
}
