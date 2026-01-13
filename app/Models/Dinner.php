<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dinner extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['uid', 'title', 'complete', 'date', 'event', 'protein_id'];
    protected $casts = [
        'event' => 'array',
    ];
    protected $appends = ['recipe_url'];

    public function protein(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Protein::class);
    }

    public function getRecipeUrlAttribute()
    {
        return $this->event['location'] ?? null;
    }

    public function guessProtien()
    {
        $protein = Dinner::whereNotNull('protein_id')->where('title', 'like', $this->title)->first()?->protein;
        $proteins = Protein::get();
        $dinner = $this;
        if (!$protein) {
            $protein = $proteins->filter(function ($protein) use ($dinner) {
                return is_numeric(stripos($dinner->title, $protein->name));
            })->first();
        }
        if (!$protein) {


            $protein = $proteins->filter(function ($protein) use ($dinner) {
                return $protein->aliases->filter(function ($alias) use ($dinner) {
                        return !empty($alias) && is_numeric(stripos($dinner->title, $alias));
                    })->count() > 0;

            });

            $protein = $protein->first();
        }

        if ($protein) {
            $this->protein_id = $protein->id;
            $this->save();
        }
    }

}
