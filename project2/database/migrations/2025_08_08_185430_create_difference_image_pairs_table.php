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
        Schema::create('difference_image_pairs', function (Blueprint $table) {
            $table->id();
            $table->string('image_1'); // المسار النسبي للصورة الأولى
            $table->string('image_2'); // المسار النسبي للصورة الثانية
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('difference_image_pairs');
    }
};
