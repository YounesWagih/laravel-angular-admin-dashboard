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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class ProductController extends Controller
{
    public function index(IndexProductsRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();
        $locale = app()->getLocale();

        $products = Product::query()
            ->with('category')
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
        return ProductDetailsResource::make($product->load('category'));
    }

    public function store(SaveProductRequest $request): JsonResponse
    {
        $image = $request->file('image')?->store('products', 'public');

        try {
            $product = Product::query()->create([
                ...$this->attributes($request),
                'image' => $image,
            ]);
        } catch (Throwable $exception) {
            if ($image) {
                Storage::disk('public')->delete($image);
            }

            throw $exception;
        }

        return ProductResource::make($product->load('category'))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(SaveProductRequest $request, Product $product): ProductResource
    {
        $newImage = $request->file('image')?->store('products', 'public');
        $oldImage = $product->image;

        try {
            $product->update([
                ...$this->attributes($request),
                'image' => $newImage ?? $oldImage,
            ]);
        } catch (Throwable $exception) {
            if ($newImage) {
                Storage::disk('public')->delete($newImage);
            }

            throw $exception;
        }

        if ($newImage && $oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        return ProductResource::make($product->load('category'));
    }

    public function destroy(Product $product): Response
    {
        $image = DB::transaction(function () use ($product): ?string {
            $product = Product::query()->lockForUpdate()->findOrFail($product->id);
            $image = $product->image;
            $product->delete();

            return $image;
        });

        if ($image) {
            Storage::disk('public')->delete($image);
        }

        return response()->noContent();
    }

    /**
     * @return array{
     *     category_id: int,
     *     name: array{en: string, ar: string},
     *     description: array<string, string>|null,
     *     price: mixed,
     *     stock: mixed,
     *     status: mixed
     * }
     */
    private function attributes(SaveProductRequest $request): array
    {
        $validated = $request->validated();
        $descriptions = array_filter([
            'en' => $validated['description_en'] ?? null,
            'ar' => $validated['description_ar'] ?? null,
        ], static fn (?string $description): bool => $description !== null);

        return [
            'category_id' => $validated['category_id'],
            'name' => [
                'en' => $validated['name_en'],
                'ar' => $validated['name_ar'],
            ],
            'description' => $descriptions === [] ? null : $descriptions,
            'price' => $validated['price'],
            'stock' => $validated['stock'],
            'status' => $validated['status'],
        ];
    }
}
