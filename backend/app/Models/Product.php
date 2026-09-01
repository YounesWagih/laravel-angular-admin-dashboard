<?php

namespace App\Models;

use App\Enums\Status;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

#[Fillable(['category_id', 'name', 'description', 'price', 'stock', 'status'])]
class Product extends Model implements HasMedia
{   
    /** @use HasFactory<ProductFactory> */
    use HasFactory, HasTranslations, InteractsWithMedia;

    public const string IMAGE_COLLECTION = 'image';

    public array $translatable = ['name', 'description'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection(self::IMAGE_COLLECTION)
            ->useDisk('public')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->singleFile();
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'status' => Status::class,
        ];
    }
}
