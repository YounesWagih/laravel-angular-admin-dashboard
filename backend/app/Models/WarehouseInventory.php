<?php

namespace App\Models;

use Database\Factories\WarehouseInventoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['warehouse_id', 'product_id', 'on_hand', 'reserved'])]
class WarehouseInventory extends Model
{
    /** @use HasFactory<WarehouseInventoryFactory> */
    use HasFactory;

    public const int MAX_QUANTITY = 4_294_967_295;

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(InventoryReservation::class);
    }

    protected function casts(): array
    {
        return ['on_hand' => 'integer', 'reserved' => 'integer'];
    }
}
