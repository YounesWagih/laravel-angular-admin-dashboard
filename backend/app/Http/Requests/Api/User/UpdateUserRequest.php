<?php

namespace App\Http\Requests\Api\User;

use App\Enums\UserType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class, 'email')->ignore($this->route('user')),
            ],
            'type' => ['sometimes', 'required', Rule::enum(UserType::class)],
            'role_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists(Role::class, 'id')->where('guard_name', 'web'),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $values = [];

        if ($this->exists('name')) {
            $values['name'] = trim((string) $this->input('name'));
        }

        if ($this->exists('email')) {
            $values['email'] = mb_strtolower(trim((string) $this->input('email')));
        }

        $this->merge($values);
    }
}
