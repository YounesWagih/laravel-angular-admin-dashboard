<?php

namespace App\Http\Resources;

use App\Models\InventoryReservation;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'subtotal' => $this->subtotal,
            'cancellation_reason' => $this->cancellation_reason,
            'customer' => $this->whenLoaded('user', fn (): array => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(function (OrderItem $item): array {
                $names = $item->product_name;

                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $names[app()->getLocale()] ?? $names[config('app.fallback_locale')] ?? reset($names),
                    'unit_price' => $item->unit_price,
                    'quantity' => $item->quantity,
                    'line_total' => $item->line_total,
                    'allocations' => $item->relationLoaded('reservations')
                        ? $item->reservations->map(fn (InventoryReservation $reservation): array => [
                            'warehouse' => [
                                'id' => $reservation->inventory->warehouse->id,
                                'code' => $reservation->inventory->warehouse->code,
                                'name' => $reservation->inventory->warehouse->name,
                            ],
                            'quantity' => $reservation->quantity,
                            'status' => $reservation->status->value,
                        ])->values()
                        : [],
                ];
            })->values()),
            'history' => $this->whenLoaded('histories', fn () => $this->histories->map(fn (OrderStatusHistory $history): array => [
                'from' => $history->from_status?->value,
                'to' => $history->to_status->value,
                'reason' => $history->reason,
                'actor' => $history->actor ? ['id' => $history->actor->id, 'name' => $history->actor->name] : null,
                'created_at' => $history->created_at,
            ])->values()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
