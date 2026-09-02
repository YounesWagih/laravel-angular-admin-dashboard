<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;

final class EloquentProductRepository implements ProductRepository
{
    public function paginate(array $filters, string $locale): LengthAwarePaginator
    {
        return Product::query()
            ->with(['category', 'media'])
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
        return $product->load(['category', 'media']);
    }

    public function storeImage(Product $product, UploadedFile $image): void
    {
        $product
            ->addMedia($image)
            ->toMediaCollection(Product::IMAGE_COLLECTION);
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }
}
