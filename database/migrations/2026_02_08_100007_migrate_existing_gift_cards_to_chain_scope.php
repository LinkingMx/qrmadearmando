<?php

use App\Models\Chain;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get the chain created in the previous data migration
        $chain = Chain::first();

        if ($chain) {
            // Assign all existing gift cards to chain scope
            $count = DB::table('gift_cards')
                ->whereNull('chain_id')
                ->update([
                    'scope' => 'chain',
                    'chain_id' => $chain->id,
                    'brand_id' => null,
                ]);

            Log::info("Migrated {$count} gift cards to chain scope with chain '{$chain->name}'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('gift_cards')->update([
            'scope' => 'chain',
            'chain_id' => null,
            'brand_id' => null,
        ]);
    }
};
