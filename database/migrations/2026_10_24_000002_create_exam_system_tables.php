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
        // 1. Tests / Exams Table
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Teacher
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('duration_minutes')->default(30);
            $table->integer('total_marks')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        // 2. Questions Table (Supports 'mcq' and 'text')
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained()->cascadeOnDelete();
            $table->string('question_type'); // 'mcq' or 'text'
            $table->text('question_text');
            $table->integer('marks')->default(1);
            $table->integer('order')->default(1);
            $table->timestamps();
        });

        // 3. Question Options Table (For MCQs)
        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->text('option_text');
            $table->boolean('is_correct')->default(false);
            $table->integer('order')->default(1);
            $table->timestamps();
        });

        // 4. Student Exam Attempts Table
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Student
            $table->string('status')->default('in_progress'); // in_progress, submitted, graded
            $table->integer('score')->default(0);
            $table->integer('total_marks')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });

        // 5. Student Answers Table (Supports both option selection and text answers)
        Schema::create('student_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('selected_option_id')->nullable()->constrained('question_options')->nullOnDelete();
            $table->text('text_answer')->nullable();
            $table->boolean('is_correct')->nullable(); // null for text questions pending review
            $table->integer('marks_awarded')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_answers');
        Schema::dropIfExists('exam_attempts');
        Schema::dropIfExists('question_options');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('tests');
    }
};
