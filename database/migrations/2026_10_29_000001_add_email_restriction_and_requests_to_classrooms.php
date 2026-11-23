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
        // 1. Add email restriction columns to classrooms table
        if (Schema::hasTable('classrooms')) {
            Schema::table('classrooms', function (Blueprint $table) {
                if (!Schema::hasColumn('classrooms', 'is_email_restricted')) {
                    $table->boolean('is_email_restricted')->default(false)->after('code');
                }
                if (!Schema::hasColumn('classrooms', 'allowed_email_domain')) {
                    $table->string('allowed_email_domain')->nullable()->after('is_email_restricted');
                }
            });
        }

        // 2. Create classroom_join_requests table
        if (!Schema::hasTable('classroom_join_requests')) {
            Schema::create('classroom_join_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('status', 20)->default('pending'); // 'pending', 'accepted', 'rejected'
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();

                $table->unique(['classroom_id', 'user_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classroom_join_requests');

        if (Schema::hasTable('classrooms')) {
            Schema::table('classrooms', function (Blueprint $table) {
                if (Schema::hasColumn('classrooms', 'allowed_email_domain')) {
                    $table->dropColumn('allowed_email_domain');
                }
                if (Schema::hasColumn('classrooms', 'is_email_restricted')) {
                    $table->dropColumn('is_email_restricted');
                }
            });
        }
    }
};
