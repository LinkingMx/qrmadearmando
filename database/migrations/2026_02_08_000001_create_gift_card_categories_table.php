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
        Schema::create('gift_card_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('prefix', 10)->unique();
            $table->string('nature'); // payment_method or discount
            $table->timestamps();

            $table->index('prefix');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gift_card_categories');
    }
};
