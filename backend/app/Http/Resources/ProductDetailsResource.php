<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

final class ProductDetailsResource extends ProductResource
{
    public function toArray(Request $request): array
    {
        $names = $this->getTranslations('name');
        $descriptions = $this->descriptionTranslations();

        return [
            ...parent::toArray($request),
            'name_en' => $names['en'] ?? '',
            'name_ar' => $names['ar'] ?? '',
            'description_en' => $descriptions['en'] ?? null,
            'description_ar' => $descriptions['ar'] ?? null,
        ];
    }
}
