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
        Schema::table('gift_cards', function (Blueprint $table) {
            $table->string('scope')->default('chain')->after('gift_card_category_id');
            $table->foreignId('chain_id')->nullable()->after('scope')->constrained()->restrictOnDelete();
            $table->foreignId('brand_id')->nullable()->after('chain_id')->constrained()->restrictOnDelete();

            $table->index('scope');
            $table->index('chain_id');
            $table->index('brand_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gift_cards', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['scope']);
            $table->dropIndex(['brand_id']);
            $table->dropIndex(['chain_id']);

            // Drop foreign keys (SQLite compatible)
            $table->dropForeign(['brand_id']);
            $table->dropForeign(['chain_id']);

            // Drop columns
            $table->dropColumn(['brand_id', 'chain_id', 'scope']);
        });
    }
};
