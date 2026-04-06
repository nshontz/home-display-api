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
        Schema::create('collection_recipe', function (Blueprint $table) {
            $table->foreignId('collection_id')->constrained('recipe_collections')->cascadeOnDelete();
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->integer('order')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['collection_id', 'recipe_id'], 'collection_recipe_primary');
            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_recipe');
    }
};
