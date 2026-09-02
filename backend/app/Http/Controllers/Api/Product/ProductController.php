<?php

namespace App\Http\Controllers\Api\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Product\IndexProductsRequest;
use App\Http\Requests\Api\Product\SaveProductRequest;
use App\Http\Resources\ProductDetailsResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class ProductController extends Controller
{
    public function __construct(private readonly ProductService $productService) {}

    public function index(IndexProductsRequest $request): AnonymousResourceCollection
    {
        $products = $this->productService
            ->paginate($request->validated(), app()->getLocale())
            ->withQueryString();

        return ProductResource::collection($products);
    }

    public function options(): JsonResponse
    {
        return response()->json([
            'data' => $this->productService->options(app()->getLocale()),
        ]);
    }

    public function show(Product $product): ProductDetailsResource
    {
        return ProductDetailsResource::make($this->productService->details($product));
    }

    public function store(SaveProductRequest $request): JsonResponse
    {
        $product = $this->productService->create($request->validated());

        return ProductResource::make($product)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(SaveProductRequest $request, Product $product): ProductResource
    {
        $product = $this->productService->update($product, $request->validated());

        return ProductResource::make($product);
    }

    public function destroy(Product $product): Response
    {
        $this->productService->delete($product);

        return response()->noContent();
    }
}
