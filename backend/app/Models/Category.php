<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

#[Fillable(['name', 'description'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, HasTranslations;

    public array $translatable = ['name', 'description'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
