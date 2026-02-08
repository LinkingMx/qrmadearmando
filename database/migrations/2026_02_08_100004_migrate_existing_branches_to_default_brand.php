<?php

use App\Models\Brand;
use App\Models\Chain;
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
        // Create default chain
        $chain = Chain::create(['name' => 'Cadenas Don Carlos']);

        // Create default brand under that chain
        $brand = Brand::create(['chain_id' => $chain->id, 'name' => 'Mochomos']);

        // Assign all existing branches to "Mochomos"
        DB::table('branches')->whereNull('brand_id')->update(['brand_id' => $brand->id]);

        // Make brand_id NOT NULL after data migration
        Schema::table('branches', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->change();
        });

        DB::table('branches')->update(['brand_id' => null]);

        Brand::where('name', 'Mochomos')->delete();
        Chain::where('name', 'Cadenas Don Carlos')->delete();
    }
};
