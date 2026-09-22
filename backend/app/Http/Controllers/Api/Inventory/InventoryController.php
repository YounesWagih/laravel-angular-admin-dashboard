<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Inventory\IndexInventoryRequest;
use App\Http\Requests\Api\Inventory\StoreInventoryAdjustmentRequest;
use App\Http\Requests\Api\Inventory\StoreInventoryTransferRequest;
use App\Http\Resources\WarehouseInventoryResource;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class InventoryController extends Controller
{
    public function __construct(private readonly InventoryService $inventoryService) {}

    public function index(IndexInventoryRequest $request): AnonymousResourceCollection
    {
        return WarehouseInventoryResource::collection(
            $this->inventoryService
                ->paginate($request->validated(), app()->getLocale())
                ->withQueryString(),
        );
    }

    public function adjust(StoreInventoryAdjustmentRequest $request): WarehouseInventoryResource
    {
        return WarehouseInventoryResource::make(
            $this->inventoryService->adjust($request->user(), $request->validated()),
        );
    }

    public function transfer(StoreInventoryTransferRequest $request): JsonResponse
    {
        $transfer = $this->inventoryService->transfer($request->user(), $request->validated());

        return response()->json([
            'data' => [
                'correlation_id' => $transfer['correlation_id'],
                'source' => WarehouseInventoryResource::make($transfer['source']),
                'destination' => WarehouseInventoryResource::make($transfer['destination']),
            ],
        ]);
    }
}
