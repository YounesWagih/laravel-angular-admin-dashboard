<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

final class CategoryDetailsResource extends CategoryResource
{
    public function toArray(Request $request): array
    {
        $names = $this->getTranslations('name');
        $descriptions = $this->description === null
            ? []
            : $this->getTranslations('description');

        return [
            ...parent::toArray($request),
            'name_en' => $names['en'] ?? '',
            'name_ar' => $names['ar'] ?? '',
            'description_en' => $descriptions['en'] ?? null,
            'description_ar' => $descriptions['ar'] ?? null,
        ];
    }
}
