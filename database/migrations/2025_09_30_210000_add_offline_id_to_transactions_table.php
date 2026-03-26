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
        // Only add column if table exists and column doesn't already exist
        if (Schema::hasTable('transactions') && ! Schema::hasColumn('transactions', 'offline_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                // Add offline_id for tracking offline sync transactions
                $table->uuid('offline_id')->nullable()->unique()->after('description');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'offline_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                // Drop unique index first (SQLite compatible)
                $table->dropUnique(['offline_id']);
                // Then drop column
                $table->dropColumn('offline_id');
            });
        }
    }
};
