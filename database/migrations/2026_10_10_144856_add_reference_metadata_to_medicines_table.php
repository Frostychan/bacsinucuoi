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
        Schema::table('medicines', function (Blueprint $table): void {
            $table->boolean('price_is_estimate')->default(false);
            $table->string('price_source', 2048)->nullable();
        });

        Schema::table('medicine_order_items', function (Blueprint $table): void {
            $table->boolean('unit_price_is_estimate')->default(false);
            $table->string('unit_price_source', 2048)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medicine_order_items', function (Blueprint $table): void {
            $table->dropColumn(['unit_price_is_estimate', 'unit_price_source']);
        });

        Schema::table('medicines', function (Blueprint $table): void {
            $table->dropColumn(['price_is_estimate', 'price_source']);
        });
    }
};
