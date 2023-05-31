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
        Schema::create('dialy_weather', function (Blueprint $table) {
            $table->id();
            $table->date('day');
            $table->decimal('high');
            $table->decimal('low')->nullable();
            $table->string('icon');
            $table->string('icon_alt');
            $table->string('short_forecast');
            $table->json('periods');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dialy_weather');
    }
};
