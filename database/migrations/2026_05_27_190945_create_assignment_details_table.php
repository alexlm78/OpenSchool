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
        Schema::create('assignment_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->morphs('evaluationable'); // for polymorphic relation to evaluations
            $table->text('description')->nullable();
            $table->string('file_requirements')->nullable(); // e.g., allowed extensions, max files
            $table->boolean('allow_late_submission')->default(false);
            $table->integer('late_penalty_percent')->default(0); // penalty per day or submission
            $table->dateTime('late_until')->nullable(); // deadline for late submissions
            $table->timestamps();

            $table->index('school_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignment_details');
    }
};
