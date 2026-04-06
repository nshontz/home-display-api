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
        Schema::create('ingredient_ingredient_tag', function (Blueprint $table) {
            $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->foreignId('ingredient_tag_id')->constrained('ingredient_tags')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['ingredient_id', 'ingredient_tag_id'], 'ingredient_tag_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredient_ingredient_tag');
    }
};
