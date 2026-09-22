<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class WarehouseResource extends JsonResource
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
            'code' => $this->code,
            'name' => $this->name,
            'priority' => $this->priority,
            'is_active' => $this->is_active,
            'inventory_count' => $this->whenCounted('inventories'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
