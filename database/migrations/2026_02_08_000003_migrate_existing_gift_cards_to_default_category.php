<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create the default "Empleados" category
        $defaultCategoryId = DB::table('gift_card_categories')->insertGetId([
            'name' => 'Empleados',
            'prefix' => 'EMCAD',
            'nature' => 'payment_method',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Assign all existing gift cards (including soft-deleted) to the default category
        DB::table('gift_cards')
            ->whereNull('gift_card_category_id')
            ->update(['gift_card_category_id' => $defaultCategoryId]);

        // Make gift_card_category_id non-nullable now that all records are assigned
        Schema::table('gift_cards', function (Blueprint $table) {
            $table->foreignId('gift_card_category_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Make nullable again
        Schema::table('gift_cards', function (Blueprint $table) {
            $table->foreignId('gift_card_category_id')->nullable()->change();
        });

        // Remove category assignment
        DB::table('gift_cards')->update(['gift_card_category_id' => null]);

        // Delete the default category
        DB::table('gift_card_categories')->where('prefix', 'EMCAD')->delete();
    }
};
