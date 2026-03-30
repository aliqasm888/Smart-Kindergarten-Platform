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
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('gender');
            $table->date('birth_date');
            $table->integer('experience_years');
            $table->string('profile_image')->nullable();
            $table->string('certificate_file')->nullable(); // صورة أو PDF
            $table->json('work_days')->nullable(); // مثل ["Monday", "Wednesday"]
            $table->string('work_hours')->nullable(); // مثل "10:00-15:00"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
