<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $descriptions = $this->getTranslations('description');
        $imageUrl = $this->getFirstMediaUrl(Product::IMAGE_COLLECTION);

        return [
            'id' => $this->id,
            'name' => $this->getTranslation('name', app()->getLocale()),
            'description' => $descriptions === []
                ? null
                : $this->getTranslation('description', app()->getLocale()),
            'image_url' => $imageUrl === '' ? null : $imageUrl,
            'price' => $this->price,
            'stock' => $this->stock,
            'status' => $this->status->value,
            'category' => $this->whenLoaded('category', fn (): array => [
                'id' => $this->category->id,
                'name' => $this->category->getTranslation('name', app()->getLocale()),
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
