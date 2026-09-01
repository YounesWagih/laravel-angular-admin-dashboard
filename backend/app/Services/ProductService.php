<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final class ProductService
{
    public function create(array $data, ?UploadedFile $image): Product
    {
        return DB::transaction(function () use ($data, $image): Product {
            $product = Product::query()->create($this->attributes($data));

            if ($image !== null) {
                $product
                    ->addMedia($image)
                    ->toMediaCollection(Product::IMAGE_COLLECTION);
            }

            return $product->load(['category', 'media']);
        });
    }

    public function update(Product $product, array $data, ?UploadedFile $image): Product
    {
        return DB::transaction(function () use ($product, $data, $image): Product {
            $product = Product::query()->lockForUpdate()->findOrFail($product->id);

            $product->update($this->attributes($data));

            if ($image !== null) {
                $product
                    ->addMedia($image)
                    ->toMediaCollection(Product::IMAGE_COLLECTION);
            }

            return $product->load(['category', 'media']);
        });
    }

    public function delete(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            $product = Product::query()->lockForUpdate()->findOrFail($product->id);
            $product->delete();
        });
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
