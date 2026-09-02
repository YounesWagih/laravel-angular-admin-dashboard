<?php

namespace App\Services;

use App\Enums\CategoryDeletionResult;
use App\Models\Category;
use App\Repositories\Contracts\CategoryRepository;
use App\Repositories\Contracts\TransactionManager;
use Illuminate\Pagination\LengthAwarePaginator;

final class CategoryService
{
    public function __construct(
        private readonly CategoryRepository $categories,
        private readonly TransactionManager $transactions,
    ) {}

    public function paginate(array $filters, string $locale): LengthAwarePaginator
    {
        return $this->categories->paginate($filters, $locale);
    }

    public function details(Category $category): Category
    {
        return $this->categories->withProductCount($category);
    }

    public function create(array $data): Category
    {
        $category = $this->categories->create($this->attributes($data));

        return $this->categories->withProductCount($category);
    }

    public function update(Category $category, array $data): Category
    {
        $category = $this->categories->update($category, $this->attributes($data));

        return $this->categories->withProductCount($category);
    }

    public function delete(Category $category): CategoryDeletionResult
    {
        return $this->transactions->run(function () use ($category): CategoryDeletionResult {
            $category = $this->categories->findForUpdate($category->id);

            if ($this->categories->hasProducts($category)) {
                return CategoryDeletionResult::HasProducts;
            }

            $this->categories->delete($category);

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
