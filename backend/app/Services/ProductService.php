<?php

namespace App\Services;

use App\Enums\Status;
use App\Models\Category;
use App\Models\Product;
use App\Repositories\Contracts\CategoryRepository;
use App\Repositories\Contracts\ProductRepository;
use App\Repositories\Contracts\TransactionManager;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Throwable;

final class ProductService
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly CategoryRepository $categories,
        private readonly TransactionManager $transactions,
    ) {}

    public function paginate(array $filters, string $locale): LengthAwarePaginator
    {
        return $this->products->paginate($filters, $locale);
    }

    public function options(string $locale): array
    {
        $categories = $this->categories
            ->orderedForOptions($locale)
            ->map(static fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->getTranslation('name', $locale),
            ])
            ->all();

        return [
            'categories' => $categories,
            'statuses' => array_map(
                static fn (Status $status): string => $status->value,
                Status::cases(),
            ),
        ];
    }

    public function details(Product $product): Product
    {
        return $this->products->withDetails($product);
    }

    public function create(array $data): Product
    {
        $imageData = $this->prepareImages($data, []);
        $product = $this->products->create($this->attributes($data));

        try {
            if ($imageData !== null) {
                $newImageIds = $this->storeImages($product, $imageData['new_images']);
                $this->products->reorderImages($this->primaryFirst($imageData, $newImageIds));
            }
        } catch (Throwable $exception) {
            $this->deleteAfterFailedCreation($product);

            throw $exception;
        }

        return $this->products->withDetails($product);
    }

    public function update(Product $product, array $data): Product
    {
        $imageData = $this->prepareImages($data, $this->products->imageIds($product));
        $newImageIds = [];

        try {
            if ($imageData !== null) {
                $newImageIds = $this->storeImages($product, $imageData['new_images']);
            }

            $product = $this->transactions->run(function () use ($product, $data, $imageData, $newImageIds): Product {
                $product = $this->products->update($product, $this->attributes($data));

                if ($imageData !== null) {
                    $this->products->reorderImages($this->primaryFirst($imageData, $newImageIds));
                    $this->products->removeImages($product, $imageData['removed_ids']);
                }

                return $product;
            });
        } catch (Throwable $exception) {
            $this->removeImagesAfterFailure($product, $newImageIds);

            throw $exception;
        }

        return $this->products->withDetails($product);
    }

    public function delete(Product $product): void
    {
        $this->products->delete($product);
    }

    private function prepareImages(array $data, array $currentImageIds): ?array
    {
        $imagesChanged = array_key_exists('new_images', $data)
            || array_key_exists('removed_image_ids', $data)
            || array_key_exists('primary_image', $data);

        if (! $imagesChanged) {
            return null;
        }

        $newImages = array_values($data['new_images'] ?? []);
        $removedImageIds = array_values(array_map('intval', $data['removed_image_ids'] ?? []));
        $remainingImageIds = array_values(array_diff($currentImageIds, $removedImageIds));
        $primaryImage = $data['primary_image'] ?? null;

        if (array_diff($removedImageIds, $currentImageIds) !== []) {
            $this->invalidImages('removed_image_ids', 'A removed image does not belong to this product.');
        }

        if (count($remainingImageIds) + count($newImages) > Product::MAX_IMAGES) {
            $this->invalidImages(
                'new_images',
                'A product may have at most '.Product::MAX_IMAGES.' images.',
            );
        }

        if (is_string($primaryImage)) {
            $existingImageIsValid = str_starts_with($primaryImage, 'existing:')
                && in_array((int) substr($primaryImage, 9), $remainingImageIds, true);
            $newImageIsValid = str_starts_with($primaryImage, 'new:')
                && isset($newImages[(int) substr($primaryImage, 4)]);

            if (! $existingImageIsValid && ! $newImageIsValid) {
                $this->invalidImages('primary_image', 'The primary image does not belong to this product.');
            }
        }

        return [
            'new_images' => $newImages,
            'removed_ids' => $removedImageIds,
            'remaining_ids' => $remainingImageIds,
            'primary_image' => $primaryImage,
        ];
    }

    private function primaryFirst(array $imageData, array $newImageIds): array
    {
        $imageIds = [...$imageData['remaining_ids'], ...$newImageIds];
        $primaryImage = $imageData['primary_image'];

        if ($imageIds === [] || $primaryImage === null) {
            return $imageIds;
        }

        $primaryImageId = str_starts_with($primaryImage, 'existing:')
            ? (int) substr($primaryImage, 9)
            : $newImageIds[(int) substr($primaryImage, 4)];

        return [
            $primaryImageId,
            ...array_values(array_diff($imageIds, [$primaryImageId])),
        ];
    }

    private function storeImages(Product $product, array $images): array
    {
        $imageIds = [];

        try {
            foreach ($images as $image) {
                $imageIds[] = $this->products->storeImage($product, $image);
            }
        } catch (Throwable $exception) {
            $this->removeImagesAfterFailure($product, $imageIds);

            throw $exception;
        }

        return $imageIds;
    }

    private function removeImagesAfterFailure(Product $product, array $imageIds): void
    {
        if ($imageIds === []) {
            return;
        }

        try {
            $this->products->removeImages($product, $imageIds);
        } catch (Throwable $cleanupException) {
            report($cleanupException);
        }
    }

    private function invalidImages(string $field, string $message): never
    {
        throw ValidationException::withMessages([
            $field => [$message],
        ]);
    }

    private function deleteAfterFailedCreation(Product $product): void
    {
        try {
            $this->products->delete($product);
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
