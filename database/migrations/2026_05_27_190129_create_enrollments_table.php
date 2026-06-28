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
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('course_offering_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('active'); // active, completed, dropped, etc.
            $table->date('enrolled_at')->nullable();
            $table->date('completed_at')->nullable();
            $table->timestamps();

            $table->index('school_id');
            $table->index('student_id');
            $table->index('course_offering_id');
            $table->index('status');
            $table->unique(['school_id', 'student_id', 'course_offering_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
