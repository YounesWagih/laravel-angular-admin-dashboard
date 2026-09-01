<?php

namespace App\Http\Controllers\Api\Category;

use App\Enums\CategoryDeletionResult;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Category\IndexCategoriesRequest;
use App\Http\Requests\Api\Category\SaveCategoryRequest;
use App\Http\Resources\CategoryDetailsResource;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categoryService) {}

    public function index(IndexCategoriesRequest $request): AnonymousResourceCollection
    {
        $categories = $this->categoryService
            ->paginate($request->validated(), app()->getLocale())
            ->withQueryString();

        return CategoryResource::collection($categories);
    }

    public function show(Category $category): CategoryDetailsResource
    {
        return CategoryDetailsResource::make($category->loadCount('products'));
    }

    public function store(SaveCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->create($request->validated());

        return CategoryResource::make($category)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(SaveCategoryRequest $request, Category $category): CategoryResource
    {
        $category = $this->categoryService->update($category, $request->validated());

        return CategoryResource::make($category);
    }

    public function destroy(Category $category): Response|JsonResponse
    {
        return match ($this->categoryService->delete($category)) {
            CategoryDeletionResult::Deleted => response()->noContent(),
            CategoryDeletionResult::HasProducts => response()->json([
                'message' => 'A category containing products cannot be deleted.',
            ], Response::HTTP_CONFLICT),
        };
    }
}
