<?php

namespace App\Repositories\Eloquent;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class EloquentCategoryRepository implements CategoryRepository
{
    public function paginate(array $filters, string $locale): LengthAwarePaginator
    {
        return Category::query()
            ->withCount('products')
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->whereRaw("LOWER(name->>'$.en') LIKE LOWER(?)", ["%{$search}%"])
                        ->orWhere('name->ar', 'like', "%{$search}%");
                });
            })
            ->orderBy("name->{$locale}")
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 10);
    }

    public function orderedForOptions(string $locale): Collection
    {
        return Category::query()
            ->orderBy("name->{$locale}")
            ->get();
    }

    public function create(array $attributes): Category
    {
        return Category::query()->create($attributes);
    }

    public function update(Category $category, array $attributes): Category
    {
        $category->update($attributes);

        return $category;
    }

    public function withProductCount(Category $category): Category
    {
        return $category->loadCount('products');
    }

    public function findForUpdate(int $categoryId): Category
    {
        return Category::query()->lockForUpdate()->findOrFail($categoryId);
    }

    public function hasProducts(Category $category): bool
    {
        return $category->products()->exists();
    }

    public function delete(Category $category): void
    {
        $category->delete();
    }
}
