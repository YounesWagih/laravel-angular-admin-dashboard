<?php

namespace App\Http\Requests\Api\Role;

use App\Models\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SyncRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permissions' => ['present', 'array'],
            'permissions.*' => [
                'string',
                'distinct',
                Rule::exists(Permission::class, 'name')->where('guard_name', 'web'),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! is_array($this->input('permissions'))) {
            return;
        }

        $this->merge([
            'permissions' => array_map(
                static fn (mixed $permission): mixed => is_string($permission) ? trim($permission) : $permission,
                $this->input('permissions'),
            ),
        ]);
    }
}
