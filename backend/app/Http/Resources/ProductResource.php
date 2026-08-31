<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin Product */
class ProductResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $descriptions = $this->descriptionTranslations();

        return [
            'id' => $this->id,
            'name' => $this->getTranslation('name', app()->getLocale()),
            'description' => $descriptions === []
                ? null
                : $this->getTranslation('description', app()->getLocale()),
            'image_url' => $this->image === null
                ? null
                : Storage::disk('public')->url($this->image),
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

    /** @return array<string, string> */
    protected function descriptionTranslations(): array
    {
        return array_filter(
            $this->getTranslations('description'),
            static fn (?string $description): bool => $description !== null && $description !== '',
        );
    }
}
