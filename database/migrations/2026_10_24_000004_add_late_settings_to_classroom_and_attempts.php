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
        // 1. Add allow_late_submissions to classroom_test table
        if (Schema::hasTable('classroom_test') && !Schema::hasColumn('classroom_test', 'allow_late_submissions')) {
            Schema::table('classroom_test', function (Blueprint $table) {
                $table->boolean('allow_late_submissions')->default(false)->after('is_active');
            });
        }

        // 2. Add is_late flag to exam_attempts table
        if (Schema::hasTable('exam_attempts') && !Schema::hasColumn('exam_attempts', 'is_late')) {
            Schema::table('exam_attempts', function (Blueprint $table) {
                $table->boolean('is_late')->default(false)->after('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('classroom_test') && Schema::hasColumn('classroom_test', 'allow_late_submissions')) {
            Schema::table('classroom_test', function (Blueprint $table) {
                $table->dropColumn('allow_late_submissions');
            });
        }

        if (Schema::hasTable('exam_attempts') && Schema::hasColumn('exam_attempts', 'is_late')) {
            Schema::table('exam_attempts', function (Blueprint $table) {
                $table->dropColumn('is_late');
            });
        }
    }
};
