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
        Schema::table('student_answers', function (Blueprint $table) {
            $table->decimal('marks_awarded', 8, 2)->default(0)->change();
        });

        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->decimal('score', 8, 2)->default(0)->change();
            $table->decimal('total_marks', 8, 2)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_answers', function (Blueprint $table) {
            $table->integer('marks_awarded')->default(0)->change();
        });

        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->integer('score')->default(0)->change();
            $table->integer('total_marks')->default(0)->change();
        });
    }
};
