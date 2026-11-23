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
        Schema::table('cheating_events', function (Blueprint $table) {
            if (!Schema::hasColumn('cheating_events', 'exam_attempt_id')) {
                $table->foreignId('exam_attempt_id')->nullable()->after('user_id')->constrained('exam_attempts')->nullOnDelete();
            }
        });

        Schema::table('student_answers', function (Blueprint $table) {
            if (!Schema::hasColumn('student_answers', 'teacher_feedback')) {
                $table->text('teacher_feedback')->nullable()->after('marks_awarded');
            }
            if (!Schema::hasColumn('student_answers', 'is_graded')) {
                $table->boolean('is_graded')->default(false)->after('teacher_feedback');
            }
        });

        Schema::table('exam_attempts', function (Blueprint $table) {
            if (!Schema::hasColumn('exam_attempts', 'teacher_feedback')) {
                $table->text('teacher_feedback')->nullable()->after('score');
            }
            if (!Schema::hasColumn('exam_attempts', 'graded_at')) {
                $table->timestamp('graded_at')->nullable()->after('teacher_feedback');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            if (Schema::hasColumn('exam_attempts', 'graded_at')) {
                $table->dropColumn('graded_at');
            }
            if (Schema::hasColumn('exam_attempts', 'teacher_feedback')) {
                $table->dropColumn('teacher_feedback');
            }
        });

        Schema::table('student_answers', function (Blueprint $table) {
            if (Schema::hasColumn('student_answers', 'is_graded')) {
                $table->dropColumn('is_graded');
            }
            if (Schema::hasColumn('student_answers', 'teacher_feedback')) {
                $table->dropColumn('teacher_feedback');
            }
        });

        Schema::table('cheating_events', function (Blueprint $table) {
            if (Schema::hasColumn('cheating_events', 'exam_attempt_id')) {
                $table->dropForeign(['exam_attempt_id']);
                $table->dropColumn('exam_attempt_id');
            }
        });
    }
};
