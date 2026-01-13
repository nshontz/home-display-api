<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Protein extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'vegetarian', 'color', 'aka'];

    public function dinners(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Dinner::class);
    }

    public function getAliasesAttribute(){
        if (empty($this->aka)) {
            return collect([]);
        }
        return collect(explode(',',$this->aka))->map(function($alias){
            return trim($alias);
        });
    }
}
