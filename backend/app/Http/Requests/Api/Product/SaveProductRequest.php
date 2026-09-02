<?php

namespace App\Http\Requests\Api\Product;

use App\Enums\Status;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'description_en' => ['nullable', 'string', 'max:5000'],
            'description_ar' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['required', 'integer', Rule::exists(Category::class, 'id')],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'stock' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'status' => ['required', Rule::enum(Status::class)],
            'new_images' => ['sometimes', 'array', 'max:'.Product::MAX_IMAGES],
            'new_images.*' => [
                'required',
                'image',
                'mimes:'.implode(',', Product::IMAGE_EXTENSIONS),
                'max:'.Product::MAX_IMAGE_SIZE_KILOBYTES,
            ],
            'primary_image' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                'regex:/^(existing:[1-9][0-9]*|new:(?:0|[1-9][0-9]*))$/',
            ],
            'removed_image_ids' => ['sometimes', 'array', 'max:'.Product::MAX_IMAGES],
            'removed_image_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $values = [];

        foreach (['name_en', 'name_ar', 'description_en', 'description_ar'] as $field) {
            if (! $this->exists($field)) {
                continue;
            }

            $value = trim((string) $this->input($field));
            $values[$field] = str_starts_with($field, 'description_') && $value === ''
                ? null
                : $value;
        }

        $removedImageIds = $this->input('removed_image_ids');

        if (is_string($removedImageIds)) {
            $decodedValue = json_decode($removedImageIds, true);

            if (is_array($decodedValue)) {
                $values['removed_image_ids'] = $decodedValue;
            }
        }

        $this->merge($values);
    }
}
