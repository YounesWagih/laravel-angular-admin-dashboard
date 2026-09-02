<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class ProductDetailsResource extends ProductResource
{
    public function toArray(Request $request): array
    {
        $names = $this->getTranslations('name');
        $descriptions = $this->getTranslations('description');
        $images = $this
            ->getMedia(Product::IMAGE_COLLECTION)
            ->values()
            ->map(static fn (Media $media, int $index): array => [
                'id' => $media->id,
                'url' => $media->getUrl(),
                'file_name' => $media->file_name,
                'size' => $media->size,
                'order' => $index + 1,
                'is_primary' => $index === 0,
            ])
            ->all();

        return [
            ...parent::toArray($request),
            'name_en' => $names['en'] ?? '',
            'name_ar' => $names['ar'] ?? '',
            'description_en' => $descriptions['en'] ?? null,
            'description_ar' => $descriptions['ar'] ?? null,
            'images' => $images,
        ];
    }
}
