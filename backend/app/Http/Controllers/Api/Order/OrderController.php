<?php

namespace App\Http\Controllers\Api\Order;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Order\IndexOrdersRequest;
use App\Http\Requests\Api\Order\StoreOrderRequest;
use App\Http\Requests\Api\Order\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

final class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    public function index(IndexOrdersRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Order::class);

        return OrderResource::collection(
            $this->orderService->paginate($request->user(), $request->validated())->withQueryString(),
        );
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        Gate::authorize('create', Order::class);
        $result = $this->orderService->submit(
            $request->user(),
            $request->safe()->only('items'),
            $request->validated('idempotency_key'),
        );

        return OrderResource::make($result['order'])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED)
            ->header('Idempotent-Replay', $result['replayed'] ? 'true' : 'false');
    }

    public function show(Order $order): OrderResource
    {
        Gate::authorize('view', $order);

        return OrderResource::make($this->orderService->details($order));
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): OrderResource
    {
        $target = OrderStatus::from($request->validated('status'));

        return OrderResource::make($this->orderService->transition(
            $order,
            $request->user(),
            $target,
            $request->validated('reason'),
        ));
    }
}
