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
        // This migration is no longer needed - the initial migration now uses foreignUuid()
        // Keeping this as a no-op for backwards compatibility with existing migrations
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op - see up() method for details
    }
};
