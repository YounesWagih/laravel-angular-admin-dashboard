<?php

namespace App\Http\Controllers\Api\Warehouse;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Warehouse\IndexWarehousesRequest;
use App\Http\Requests\Api\Warehouse\StoreWarehouseRequest;
use App\Http\Requests\Api\Warehouse\UpdateWarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;
use App\Services\WarehouseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class WarehouseController extends Controller
{
    public function __construct(private readonly WarehouseService $warehouseService) {}

    public function index(IndexWarehousesRequest $request): AnonymousResourceCollection
    {
        return WarehouseResource::collection(
            $this->warehouseService->paginate($request->validated())->withQueryString(),
        );
    }

    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        return WarehouseResource::make($this->warehouseService->create($request->validated()))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Warehouse $warehouse): WarehouseResource
    {
        return WarehouseResource::make($this->warehouseService->details($warehouse));
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): WarehouseResource
    {
        return WarehouseResource::make($this->warehouseService->update($warehouse, $request->validated()));
    }

    public function destroy(Warehouse $warehouse): Response
    {
        $this->warehouseService->delete($warehouse);

        return response()->noContent();
    }
}
