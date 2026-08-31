<?php

namespace App\Http\Requests\Api\User;

use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', Rule::enum(UserType::class)],
            'role_id' => [
                'nullable',
                'integer',
                Rule::exists(Role::class, 'id')->where('guard_name', 'web'),
            ],
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
