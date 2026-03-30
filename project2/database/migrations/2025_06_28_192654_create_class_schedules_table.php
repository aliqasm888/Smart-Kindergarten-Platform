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
        Schema::create('class_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained('class_rooms')->cascadeOnUpdate()->cascadeOnDelete();            $table->enum('day', ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday']);
            $table->string('period_1')->nullable();
            $table->string('period_2')->nullable();
            $table->string('period_3')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_schedules');
    }
};
