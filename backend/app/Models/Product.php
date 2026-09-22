<?php

namespace App\Models;

use App\Enums\Status;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

#[Fillable(['category_id', 'name', 'description', 'price', 'status'])]
class Product extends Model implements HasMedia
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, HasTranslations, InteractsWithMedia;

    public const string IMAGE_COLLECTION = 'image';

    public const int MAX_IMAGES = 5;

    public const int MAX_IMAGE_SIZE_KILOBYTES = 2048;

    public const array IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public const array IMAGE_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public array $translatable = ['name', 'description'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(WarehouseInventory::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection(self::IMAGE_COLLECTION)
            ->useDisk('public')
            ->acceptsMimeTypes(self::IMAGE_MIME_TYPES);
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'status' => Status::class,
        ];
    }
}
