<?php

namespace App\Http\Requests\Api\Inventory;

use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreInventoryAdjustmentRequest extends FormRequest
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
            'warehouse_id' => ['required', 'integer', Rule::exists(Warehouse::class, 'id')],
            'product_id' => ['required', 'integer', Rule::exists(Product::class, 'id')],
            'quantity_delta' => ['required', 'integer', 'between:-2147483648,2147483647', 'not_in:0'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
