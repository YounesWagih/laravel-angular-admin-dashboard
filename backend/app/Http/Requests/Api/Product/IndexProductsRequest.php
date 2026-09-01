<?php

namespace App\Http\Requests\Api\Product;

use App\Enums\Status;
use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexProductsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', Rule::exists(Category::class, 'id')],
            'status' => ['nullable', Rule::enum(Status::class)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->exists('search')) {
            return;
        }

        $search = trim((string) $this->input('search'));

        $this->merge([
            'search' => $search === '' ? null : $search,
        ]);
    }
}
