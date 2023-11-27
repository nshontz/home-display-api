<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('proteins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('vegetarian')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        $proteins = ['Chicken','Beef','Venison','Turkey','Tofu'];

        foreach($proteins as $proteinName){
            $protein  = new \App\Models\Protein();
            $protein->name = $proteinName;
            $protein->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proteins');
    }
};
