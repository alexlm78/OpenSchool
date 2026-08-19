<?php

declare(strict_types=1);

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
        Schema::create('course_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_period_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_template_id')->constrained()->onDelete('cascade');
            $table->integer('capacity')->default(0);
            $table->string('section_name')->nullable(); // e.g., "A", "B", "Morning", etc.
            $table->timestamps();

            $table->index('school_id');
            $table->index('academic_period_id');
            $table->index('course_template_id');
            $table->unique(['school_id', 'academic_period_id', 'course_template_id', 'section_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_offerings');
    }
};
