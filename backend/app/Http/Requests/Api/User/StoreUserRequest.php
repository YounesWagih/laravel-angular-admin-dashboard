<?php

namespace App\Http\Requests\Api\User;

use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class, 'email'),
            ],
            'password' => ['required', Password::defaults()],
            'type' => ['required', Rule::enum(UserType::class)],
            'role_id' => [
                'required',
                'integer',
                Rule::exists(Role::class, 'id')->where('guard_name', 'web'),
            ],
            'status' => ['required', Rule::enum(Status::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }
}
