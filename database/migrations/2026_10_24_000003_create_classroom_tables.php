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
        // 1. Classrooms Table
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Teacher owner
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('code', 10)->unique();
            $table->timestamps();
        });

        // 2. Classroom - Student Pivot Table (Enrollment)
        Schema::create('classroom_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Student
            $table->timestamps();

            $table->unique(['classroom_id', 'user_id']);
        });

        // 3. Classroom - Test Pivot Table (Assignment with availability window)
        Schema::create('classroom_test', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_id')->constrained()->cascadeOnDelete();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['classroom_id', 'test_id']);
        });

        // 4. Add classroom_id to exam_attempts if not present
        if (Schema::hasTable('exam_attempts') && !Schema::hasColumn('exam_attempts', 'classroom_id')) {
            Schema::table('exam_attempts', function (Blueprint $table) {
                $table->foreignId('classroom_id')
                    ->nullable()
                    ->after('test_id')
                    ->constrained('classrooms')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('exam_attempts') && Schema::hasColumn('exam_attempts', 'classroom_id')) {
            Schema::table('exam_attempts', function (Blueprint $table) {
                $table->dropForeign(['classroom_id']);
                $table->dropColumn('classroom_id');
            });
        }

        Schema::dropIfExists('classroom_test');
        Schema::dropIfExists('classroom_student');
        Schema::dropIfExists('classrooms');
    }
};
