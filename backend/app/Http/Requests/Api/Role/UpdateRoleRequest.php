<?php

namespace App\Http\Requests\Api\Role;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateRoleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique(Role::class, 'name')
                    ->where('guard_name', 'web')
                    ->ignore($this->route('role')),
            ],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $values = [];

        if ($this->exists('name')) {
            $values['name'] = trim((string) $this->input('name'));
        }

        if ($this->exists('description')) {
            $description = trim((string) $this->input('description'));
            $values['description'] = $description === '' ? null : $description;
        }

        $this->merge($values);
    }
}
