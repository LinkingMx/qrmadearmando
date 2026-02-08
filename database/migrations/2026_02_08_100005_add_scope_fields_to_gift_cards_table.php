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
            $table->dropIndex(['scope']);
            $table->dropConstrainedForeignId('brand_id');
            $table->dropConstrainedForeignId('chain_id');
            $table->dropColumn('scope');
        });
    }
};
