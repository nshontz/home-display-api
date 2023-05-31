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
        Schema::rename('dialy_weather', 'daily_weather');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('daily_weather', 'dialy_weather');
    }
};
