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
        Schema::table('roles', function (Blueprint $table) {
            if (! Schema::hasColumn('roles', 'school_id')) {
                $table->foreignId('school_id')->nullable()->constrained()->onDelete('cascade');
                $table->index('school_id');
            }
        });

        Schema::table('permissions', function (Blueprint $table) {
            if (! Schema::hasColumn('permissions', 'school_id')) {
                $table->foreignId('school_id')->nullable()->constrained()->onDelete('cascade');
                $table->index('school_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (Schema::hasColumn('roles', 'school_id')) {
                $table->dropForeign(['school_id']);
                $table->dropIndex(['school_id']);
                $table->dropColumn('school_id');
            }
        });

        Schema::table('permissions', function (Blueprint $table) {
            if (Schema::hasColumn('permissions', 'school_id')) {
                $table->dropForeign(['school_id']);
                $table->dropIndex(['school_id']);
                $table->dropColumn('school_id');
            }
        });
    }
};
