<?php

namespace App\Repositories\Contracts;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProductRepository
{
    public function paginate(array $filters, string $locale): LengthAwarePaginator;

    public function create(array $attributes): Product;

    public function update(Product $product, array $attributes): Product;

    public function withDetails(Product $product): Product;

    public function imageIds(Product $product): array;

    public function storeImage(Product $product, UploadedFile $image): int;

    public function removeImages(Product $product, array $imageIds): void;

    public function reorderImages(array $imageIds): void;

    public function findForUpdate(int $productId): Product;

    public function hasInventoryOrOrders(Product $product): bool;

    public function delete(Product $product): void;
}
