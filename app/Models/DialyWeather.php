<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DialyWeather extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['day'];

    protected $casts = ['periods'];
}
