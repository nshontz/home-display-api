<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RecipeCollection extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'cover_image_path',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function recipes(): BelongsToMany
    {
        return $this->belongsToMany(Recipe::class, 'collection_recipe')
            ->withPivot('order')
            ->withTimestamps()
            ->orderByPivot('order');
    }
}
