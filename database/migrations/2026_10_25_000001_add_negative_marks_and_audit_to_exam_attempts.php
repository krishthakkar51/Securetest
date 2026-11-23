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
        Schema::table('exam_attempts', function (Blueprint $table) {
            if (!Schema::hasColumn('exam_attempts', 'negative_marks')) {
                $table->decimal('negative_marks', 8, 2)->default(0.00)->after('score');
            }
            if (!Schema::hasColumn('exam_attempts', 'negative_marks_reason')) {
                $table->text('negative_marks_reason')->nullable()->after('negative_marks');
            }
        });

        if (!Schema::hasTable('mark_deduction_audits')) {
            Schema::create('mark_deduction_audits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('exam_attempt_id')->constrained('exam_attempts')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Teacher
                $table->decimal('original_score', 8, 2);
                $table->decimal('negative_marks', 8, 2);
                $table->decimal('final_marks', 8, 2);
                $table->text('reason');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mark_deduction_audits');

        Schema::table('exam_attempts', function (Blueprint $table) {
            if (Schema::hasColumn('exam_attempts', 'negative_marks_reason')) {
                $table->dropColumn('negative_marks_reason');
            }
            if (Schema::hasColumn('exam_attempts', 'negative_marks')) {
                $table->dropColumn('negative_marks');
            }
        });
    }
};
