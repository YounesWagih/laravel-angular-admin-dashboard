<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class EloquentProductRepository implements ProductRepository
{
    public function paginate(array $filters, string $locale): LengthAwarePaginator
    {
        return Product::query()
            ->with(['category', 'media'])
            ->withSum('inventories as stock_on_hand', 'on_hand')
            ->withSum('inventories as stock_reserved', 'reserved')
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->whereRaw("LOWER(name->>'$.en') LIKE LOWER(?)", ["%{$search}%"])
                        ->orWhere('name->ar', 'like', "%{$search}%");
                });
            })
            ->when(
                $filters['category_id'] ?? null,
                fn (Builder $query, int $categoryId): Builder => $query->where('category_id', $categoryId),
            )
            ->when(
                $filters['status'] ?? null,
                fn (Builder $query, string $status): Builder => $query->where('status', $status),
            )
            ->orderBy("name->{$locale}")
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 10);
    }

    public function create(array $attributes): Product
    {
        return Product::query()->create($attributes);
    }

    public function update(Product $product, array $attributes): Product
    {
        $product->update($attributes);

        return $product;
    }

    public function withDetails(Product $product): Product
    {
        return $product
            ->load(['category', 'media'])
            ->loadSum('inventories as stock_on_hand', 'on_hand')
            ->loadSum('inventories as stock_reserved', 'reserved');
    }

    public function imageIds(Product $product): array
    {
        return $product
            ->media()
            ->where('collection_name', Product::IMAGE_COLLECTION)
            ->ordered()
            ->pluck('id')
            ->map(static fn (int|string $imageId): int => (int) $imageId)
            ->all();
    }

    public function storeImage(Product $product, UploadedFile $image): int
    {
        $media = $product
            ->addMedia($image)
            ->toMediaCollection(Product::IMAGE_COLLECTION);

        return $media->id;
    }

    public function removeImages(Product $product, array $imageIds): void
    {
        if ($imageIds === []) {
            return;
        }

        $product
            ->media()
            ->where('collection_name', Product::IMAGE_COLLECTION)
            ->whereIn('id', $imageIds)
            ->get()
            ->each->delete();
    }

    public function reorderImages(array $imageIds): void
    {
        Media::setNewOrder($imageIds);
    }

    public function findForUpdate(int $productId): Product
    {
        return Product::query()->lockForUpdate()->findOrFail($productId);
    }

    public function hasInventoryOrOrders(Product $product): bool
    {
        return $product->inventories()->exists() || $product->orderItems()->exists();
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }
}
