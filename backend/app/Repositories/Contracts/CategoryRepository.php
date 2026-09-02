<?php

namespace App\Repositories\Contracts;

use App\Models\Category;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface CategoryRepository
{
    public function paginate(array $filters, string $locale): LengthAwarePaginator;

    /** @return Collection<int, Category> */
    public function orderedForOptions(string $locale): Collection;

    public function create(array $attributes): Category;

    public function update(Category $category, array $attributes): Category;

    public function withProductCount(Category $category): Category;

    public function findForUpdate(int $categoryId): Category;

    public function hasProducts(Category $category): bool;

    public function delete(Category $category): void;
}
