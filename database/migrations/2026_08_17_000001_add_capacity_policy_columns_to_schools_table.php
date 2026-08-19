<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            if (! Schema::hasColumn('schools', 'min_students_per_course')) {
                $table->unsignedInteger('min_students_per_course')->default(1)->after('logo');
            }
            if (! Schema::hasColumn('schools', 'max_students_per_course')) {
                $table->unsignedInteger('max_students_per_course')->default(0)->after('min_students_per_course');
            }
            if (! Schema::hasColumn('schools', 'allow_unlimited_capacity')) {
                $table->boolean('allow_unlimited_capacity')->default(true)->after('max_students_per_course');
            }
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            if (Schema::hasColumn('schools', 'allow_unlimited_capacity')) {
                $table->dropColumn('allow_unlimited_capacity');
            }
            if (Schema::hasColumn('schools', 'max_students_per_course')) {
                $table->dropColumn('max_students_per_course');
            }
            if (Schema::hasColumn('schools', 'min_students_per_course')) {
                $table->dropColumn('min_students_per_course');
            }
        });
    }
};
