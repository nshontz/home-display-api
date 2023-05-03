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
        Schema::create('dinners', function (Blueprint $table) {
            $table->id();
            $table->string('uid');
            $table->string('title');
            $table->timestamp('complete')->nullable();
            $table->timestamp('date')->nullable();
            $table->json('event')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dinners');
    }
};
