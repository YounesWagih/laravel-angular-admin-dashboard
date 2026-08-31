<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class ProductService
{
    public function create(array $data, ?UploadedFile $image): Product
    {
        $imagePath = $image?->store('products', 'public');

        try {
            $product = Product::query()->create([
                ...$this->attributes($data),
                'image' => $imagePath,
            ]);
        } catch (Throwable $exception) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            throw $exception;
        }

        return $product->load('category');
    }

    public function update(Product $product, array $data, ?UploadedFile $image): Product
    {
        $newImagePath = $image?->store('products', 'public');
        $oldImagePath = $product->image;

        try {
            $product->update([
                ...$this->attributes($data),
                'image' => $newImagePath ?: $oldImagePath,
            ]);
        } catch (Throwable $exception) {
            if ($newImagePath) {
                Storage::disk('public')->delete($newImagePath);
            }

            throw $exception;
        }

        if ($newImagePath && $oldImagePath) {
            Storage::disk('public')->delete($oldImagePath);
        }

        return $product->load('category');
    }

    public function delete(Product $product): void
    {
        $imagePath = DB::transaction(function () use ($product): ?string {
            $product = Product::query()->lockForUpdate()->findOrFail($product->id);
            $imagePath = $product->image;
            $product->delete();

            return $imagePath;
        });

        if ($imagePath) {
            Storage::disk('public')->delete($imagePath);
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
