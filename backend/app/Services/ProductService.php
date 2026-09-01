<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Throwable;

final class ProductService
{
    public function paginate(array $filters, string $locale): LengthAwarePaginator
    {
        return Product::query()
            ->with(['category', 'media'])
            ->when($filters['search'] ?? null, function (Builder $query, string $search) use ($locale): void {
                $query->where("name->{$locale}", 'like', "%{$search}%");
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

    public function create(array $data, ?UploadedFile $image): Product
    {
        $product = Product::query()->create($this->attributes($data));

        try {
            $this->addImage($product, $image);
        } catch (Throwable $exception) {
            $this->deleteAfterFailedCreation($product);

            throw $exception;
        }

        return $product->load(['category', 'media']);
    }

    public function update(Product $product, array $data, ?UploadedFile $image): Product
    {
        $product->update($this->attributes($data));
        $this->addImage($product, $image);

        return $product->load(['category', 'media']);
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    private function addImage(Product $product, ?UploadedFile $image): void
    {
        if ($image === null) {
            return;
        }

        $product
            ->addMedia($image)
            ->toMediaCollection(Product::IMAGE_COLLECTION);
    }

    private function deleteAfterFailedCreation(Product $product): void
    {
        try {
            $product->delete();
        } catch (Throwable $cleanupException) {
            report($cleanupException);
        }
    }

    private function attributes(array $data): array
    {
        $descriptions = array_filter([
            'en' => $data['description_en'] ?? null,
            'ar' => $data['description_ar'] ?? null,
        ], static fn (?string $description): bool => $description !== null);

        return [
            'category_id' => $data['category_id'],
            'name' => [
                'en' => $data['name_en'],
                'ar' => $data['name_ar'],
            ],
            'description' => $descriptions === [] ? null : $descriptions,
            'price' => $data['price'],
            'stock' => $data['stock'],
            'status' => $data['status'],
        ];
    }
}
