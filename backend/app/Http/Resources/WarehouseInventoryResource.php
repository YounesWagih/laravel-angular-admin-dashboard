<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class WarehouseInventoryResource extends JsonResource
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
            'warehouse' => $this->whenLoaded('warehouse', fn (): array => [
                'id' => $this->warehouse->id,
                'code' => $this->warehouse->code,
                'name' => $this->warehouse->name,
                'priority' => $this->warehouse->priority,
                'is_active' => $this->warehouse->is_active,
            ]),
            'product' => $this->whenLoaded('product', fn (): array => [
                'id' => $this->product->id,
                'name' => $this->product->getTranslation('name', app()->getLocale()),
            ]),
            'on_hand' => $this->on_hand,
            'reserved' => $this->reserved,
            'available' => $this->on_hand - $this->reserved,
            'updated_at' => $this->updated_at,
        ];
    }
}
