<?php

namespace App\Http\Requests\Api\Inventory;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseInventory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreInventoryTransferRequest extends FormRequest
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
            'from_warehouse_id' => ['required', 'integer', Rule::exists(Warehouse::class, 'id')],
            'to_warehouse_id' => ['required', 'integer', 'different:from_warehouse_id', Rule::exists(Warehouse::class, 'id')],
            'product_id' => ['required', 'integer', Rule::exists(Product::class, 'id')],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.WarehouseInventory::MAX_QUANTITY],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
