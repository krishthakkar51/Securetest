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
        if (Schema::hasTable('active_sessions') && !Schema::hasColumn('active_sessions', 'last_activity')) {
            Schema::table('active_sessions', function (Blueprint $table) {
                $table->timestamp('last_activity')->nullable()->after('session_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('active_sessions') && Schema::hasColumn('active_sessions', 'last_activity')) {
            Schema::table('active_sessions', function (Blueprint $table) {
                $table->dropColumn('last_activity');
            });
        }
    }
};
