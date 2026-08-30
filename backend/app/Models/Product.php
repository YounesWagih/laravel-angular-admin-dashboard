<?php

namespace App\Models;

use App\Enums\Status;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

#[Fillable(['category_id', 'name', 'description', 'image', 'price', 'stock', 'status'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, HasTranslations;

    public array $translatable = ['name', 'description'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'status' => Status::class,
        ];
    }
}
