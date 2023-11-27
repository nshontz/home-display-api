<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dinner extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['uid'];
    protected $casts = [
        'event' => 'array',
    ];

    public function protein(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Protein::class);
    }
}
