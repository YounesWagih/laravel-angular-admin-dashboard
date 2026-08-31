<?php

namespace App\Http\Controllers\Api\Category;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Category\IndexCategoriesRequest;
use App\Http\Requests\Api\Category\SaveCategoryRequest;
use App\Http\Resources\CategoryDetailsResource;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

final class CategoryController extends Controller
{
    public function index(IndexCategoriesRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();
        $locale = app()->getLocale();

        $categories = Category::query()
            ->withCount('products')
            ->when($validated['search'] ?? null, function (Builder $query, string $search) use ($locale): void {
                $query->where("name->{$locale}", 'like', "%{$search}%");
            })
            ->orderBy("name->{$locale}")
            ->paginate($validated['per_page'] ?? 10)
            ->withQueryString();

        return CategoryResource::collection($categories);
    }

    public function show(Category $category): CategoryDetailsResource
    {
        return CategoryDetailsResource::make($category->loadCount('products'));
    }

    public function store(SaveCategoryRequest $request): JsonResponse
    {
        $category = Category::query()->create($this->attributes($request));

        return CategoryResource::make($category->loadCount('products'))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(SaveCategoryRequest $request, Category $category): CategoryResource
    {
        $category->update($this->attributes($request));

        return CategoryResource::make($category->loadCount('products'));
    }

    public function destroy(Category $category): Response|JsonResponse
    {
        $deleted = DB::transaction(function () use ($category): bool {
            $category = Category::query()->lockForUpdate()->findOrFail($category->id);

            if ($category->products()->exists()) {
                return false;
            }

            return (bool) $category->delete();
        });

        if (! $deleted) {
            return response()->json([
                'message' => 'A category containing products cannot be deleted.',
            ], Response::HTTP_CONFLICT);
        }

        return response()->noContent();
    }

    private function attributes(SaveCategoryRequest $request): array
    {
        $validated = $request->validated();
        $descriptions = array_filter([
            'en' => $validated['description_en'] ?? null,
            'ar' => $validated['description_ar'] ?? null,
        ], static fn (?string $description): bool => $description !== null);

        return [
            'name' => [
                'en' => $validated['name_en'],
                'ar' => $validated['name_ar'],
            ],
            'description' => $descriptions === [] ? null : $descriptions,
        ];
    }
}
