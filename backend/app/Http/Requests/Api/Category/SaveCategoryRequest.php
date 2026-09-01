<?php

namespace App\Http\Requests\Api\Category;

use Illuminate\Foundation\Http\FormRequest;

final class SaveCategoryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'description_en' => ['nullable', 'string', 'max:1000'],
            'description_ar' => ['nullable', 'string', 'max:1000'],
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

        $this->merge($values);
    }
}
