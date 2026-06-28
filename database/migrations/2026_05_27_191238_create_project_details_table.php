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
        Schema::create('project_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->morphs('evaluationable'); // for polymorphic relation to evaluations
            $table->boolean('group_project')->default(false); // individual if false
            $table->integer('max_group_size')->nullable(); // if group_project is true
            $table->text('rubric')->nullable(); // optional rubric description or JSON
            $table->timestamps();

            $table->index('school_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_details');
    }
};
