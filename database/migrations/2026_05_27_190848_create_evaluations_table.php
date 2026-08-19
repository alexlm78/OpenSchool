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
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_offering_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('max_score', 5, 2)->default(100.00);
            $table->decimal('weight', 5, 2)->default(1.00); // weight in the course
            $table->dateTime('due_at')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->morphs('evaluationable'); // evaluationable_type and evaluationable_id
            $table->timestamps();

            $table->index('school_id');
            $table->index('course_offering_id');
            $table->index('due_at');
            $table->index('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
