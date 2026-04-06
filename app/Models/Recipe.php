<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Recipe extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'source_url',
        'source_name',
        'author',
        'servings',
        'prep_time_minutes',
        'cook_time_minutes',
        'total_time_minutes',
        'difficulty',
        'notes',
        'image_path',
        'layout_id',
        'protein_id',
        'is_published',
    ];

    protected $casts = [
        'servings' => 'integer',
        'prep_time_minutes' => 'integer',
        'cook_time_minutes' => 'integer',
        'total_time_minutes' => 'integer',
        'is_published' => 'boolean',
    ];

    public function protein(): BelongsTo
    {
        return $this->belongsTo(Protein::class);
    }

    public function layout(): BelongsTo
    {
        return $this->belongsTo(RecipeLayout::class, 'layout_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(RecipeStep::class)->orderBy('step_number');
    }

    public function recipeIngredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class)->orderBy('order');
    }

    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class, 'recipe_ingredients')
            ->withPivot(['amount', 'unit', 'preparation', 'notes', 'order', 'is_optional'])
            ->withTimestamps()
            ->orderByPivot('order');
    }

    public function dinners(): HasMany
    {
        return $this->hasMany(Dinner::class);
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(RecipeCollection::class, 'collection_recipe')
            ->withPivot('order')
            ->withTimestamps()
            ->orderByPivot('order');
    }

    public function dietaryTags()
    {
        return $this->ingredients()
            ->with('tags')
            ->get()
            ->pluck('tags')
            ->flatten()
            ->unique('id');
    }
}
