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
        Schema::create('exam_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->morphs('evaluationable'); // for polymorphic relation to evaluations
            $table->dateTime('exam_date')->nullable();
            $table->integer('duration_minutes')->nullable(); // duration of the exam
            $table->string('location')->nullable(); // room or online URL
            $table->string('modality')->default('in-person'); // in-person, online, hybrid
            $table->timestamps();

            $table->index('school_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_details');
    }
};
