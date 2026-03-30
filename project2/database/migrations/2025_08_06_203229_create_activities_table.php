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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم النشاط
            $table->enum('type', ['memory', 'attention', 'intelligence']); // نوع المهارة
            $table->enum('level', ['easy', 'medium', 'hard']); // صعوبة النشاط
            $table->string('python_script_name'); // اسم السكربت الذي ينفذه Flask
            $table->text('description')->nullable(); // وصف تفصيلي للنشاط
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
