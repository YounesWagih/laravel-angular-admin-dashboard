<?php

namespace App\Services;

use App\Enums\Status;
use App\Models\Category;
use App\Models\Product;
use App\Repositories\Contracts\CategoryRepository;
use App\Repositories\Contracts\ProductRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Throwable;

final class ProductService
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly CategoryRepository $categories,
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

    public function create(array $data, ?UploadedFile $image): Product
    {
        $product = $this->products->create($this->attributes($data));

        try {
            $this->addImage($product, $image);
        } catch (Throwable $exception) {
            $this->deleteAfterFailedCreation($product);

            throw $exception;
        }

        return $this->products->withDetails($product);
    }

    public function update(Product $product, array $data, ?UploadedFile $image): Product
    {
        $product = $this->products->update($product, $this->attributes($data));
        $this->addImage($product, $image);

        return $this->products->withDetails($product);
    }

    public function delete(Product $product): void
    {
        $this->products->delete($product);
    }

    private function addImage(Product $product, ?UploadedFile $image): void
    {
        if ($image === null) {
            return;
        }

        $this->products->storeImage($product, $image);
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
