<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Protein extends Model
{
    use HasFactory, SoftDeletes;

    public function getAliasesAttribute(){
        return collect(explode(',',$this->aka))->map(function($alias){
            return trim($alias);
        });
    }
}
