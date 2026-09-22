<?php

namespace App\Http\Requests\Api\Warehouse;

use App\Models\Warehouse;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateWarehouseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => [
                'sometimes', 'required', 'string', 'max:50', 'regex:/^[A-Z0-9_-]+$/',
                Rule::unique(Warehouse::class, 'code')->ignore($this->route('warehouse')),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'priority' => ['sometimes', 'required', 'integer', 'min:0', 'max:4294967295'],
            'is_active' => ['sometimes', 'required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $values = [];

        if ($this->exists('code')) {
            $values['code'] = mb_strtoupper(trim((string) $this->input('code')));
        }

        if ($this->exists('name')) {
            $values['name'] = trim((string) $this->input('name'));
        }

        $this->merge($values);
    }
}
