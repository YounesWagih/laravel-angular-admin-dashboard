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

    public function storeImage(Product $product, UploadedFile $image): void;

    public function delete(Product $product): void;
}
