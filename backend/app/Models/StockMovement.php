<?php

namespace App\Models;

use App\Enums\StockMovementType;
use Database\Factories\StockMovementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable(['warehouse_inventory_id', 'actor_id', 'order_id', 'type', 'on_hand_delta', 'reserved_delta', 'correlation_id', 'reason'])]
class StockMovement extends Model
{
    /** @use HasFactory<StockMovementFactory> */
    use HasFactory;

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(WarehouseInventory::class, 'warehouse_inventory_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new LogicException('Stock movements are immutable.');
        });
        static::deleting(static function (): never {
            throw new LogicException('Stock movements are immutable.');
        });
    }

    protected function casts(): array
    {
        return [
            'type' => StockMovementType::class,
            'on_hand_delta' => 'integer',
            'reserved_delta' => 'integer',
        ];
    }
}
