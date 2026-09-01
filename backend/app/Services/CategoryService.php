<?php

namespace App\Services;

use App\Enums\CategoryDeletionResult;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class CategoryService
{
    public function paginate(array $filters, string $locale): LengthAwarePaginator
    {
        return Category::query()
            ->withCount('products')
            ->when($filters['search'] ?? null, function (Builder $query, string $search) use ($locale): void {
                $query->where("name->{$locale}", 'like', "%{$search}%");
            })
            ->orderBy("name->{$locale}")
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 10);
    }

    public function create(array $data): Category
    {
        $category = Category::query()->create($this->attributes($data));

        return $category->loadCount('products');
    }

    public function update(Category $category, array $data): Category
    {
        $category->update($this->attributes($data));

        return $category->loadCount('products');
    }

    public function delete(Category $category): CategoryDeletionResult
    {
        return DB::transaction(function () use ($category): CategoryDeletionResult {
            $category = Category::query()->lockForUpdate()->findOrFail($category->id);

            if ($category->products()->exists()) {
                return CategoryDeletionResult::HasProducts;
            }

            $category->delete();

            return CategoryDeletionResult::Deleted;
        });
    }

    private function attributes(array $data): array
    {
        $descriptions = array_filter([
            'en' => $data['description_en'] ?? null,
            'ar' => $data['description_ar'] ?? null,
        ], static fn (?string $description): bool => $description !== null);

        return [
            'name' => [
                'en' => $data['name_en'],
                'ar' => $data['name_ar'],
            ],
            'description' => $descriptions === [] ? null : $descriptions,
        ];
    }
}
