<?php

namespace App\Http\Controllers\Api\Product;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Product\IndexProductsRequest;
use App\Http\Requests\Api\Product\SaveProductRequest;
use App\Http\Resources\ProductDetailsResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class ProductController extends Controller
{
    public function __construct(private readonly ProductService $productService) {}

    public function index(IndexProductsRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();
        $locale = app()->getLocale();

        $products = Product::query()
            ->with(['category', 'media'])
            ->when($validated['search'] ?? null, function (Builder $query, string $search) use ($locale): void {
                $query->where("name->{$locale}", 'like', "%{$search}%");
            })
            ->when(
                $validated['category_id'] ?? null,
                fn (Builder $query, int $categoryId): Builder => $query->where('category_id', $categoryId),
            )
            ->when(
                $validated['status'] ?? null,
                fn (Builder $query, string $status): Builder => $query->where('status', $status),
            )
            ->orderBy("name->{$locale}")
            ->paginate($validated['per_page'] ?? 10)
            ->withQueryString();

        return ProductResource::collection($products);
    }

    public function options(): JsonResponse
    {
        $locale = app()->getLocale();
        $categories = Category::query()
            ->orderBy("name->{$locale}")
            ->get()
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->getTranslation('name', $locale),
            ]);

        return response()->json([
            'data' => [
                'categories' => $categories,
                'statuses' => array_map(
                    static fn (Status $status): string => $status->value,
                    Status::cases(),
                ),
            ],
        ]);
    }

    public function show(Product $product): ProductDetailsResource
    {
        return ProductDetailsResource::make($product->load(['category', 'media']));
    }

    public function store(SaveProductRequest $request): JsonResponse
    {
        $product = $this->productService->create(
            $request->validated(),
            $request->file('image'),
        );

        return ProductResource::make($product)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(SaveProductRequest $request, Product $product): ProductResource
    {
        $product = $this->productService->update(
            $product,
            $request->validated(),
            $request->file('image'),
        );

        return ProductResource::make($product);
    }

    public function destroy(Product $product): Response
    {
        $this->productService->delete($product);

        return response()->noContent();
    }
}
