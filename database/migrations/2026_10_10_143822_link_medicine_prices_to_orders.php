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
        Schema::table('medical_record_medications', function (Blueprint $table): void {
            $table->foreignId('medicine_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::table('medicine_order_items', function (Blueprint $table): void {
            $table->foreignId('medicine_id')->nullable()->constrained()->nullOnDelete();
            $table->string('unit', 50)->nullable();
            $table->decimal('unit_price', total: 12, places: 2)->nullable();
        });

        foreach (DB::table('medicines')->get(['id', 'name']) as $medicine) {
            DB::table('medical_record_medications')
                ->whereRaw('LOWER(medicine_name) = ?', [mb_strtolower($medicine->name)])
                ->update(['medicine_id' => $medicine->id]);

            DB::table('medicine_order_items')
                ->whereRaw('LOWER(medicine_name) = ?', [mb_strtolower($medicine->name)])
                ->whereNull('medicine_id')
                ->update(['medicine_id' => $medicine->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medicine_order_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('medicine_id');
            $table->dropColumn(['unit', 'unit_price']);
        });

        Schema::table('medical_record_medications', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('medicine_id');
        });
    }
};
