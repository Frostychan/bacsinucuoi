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
        Schema::table('appointments', function (Blueprint $table): void {
            $table->string('payment_status', 30)->default('pending');
            $table->timestamp('paid_at')->nullable();
        });

        Schema::table('medicine_payments', function (Blueprint $table): void {
            $table->foreignId('appointment_id')->nullable()->after('medicine_order_id')
                ->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medicine_payments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('appointment_id');
        });

        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropColumn(['payment_status', 'paid_at']);
        });
    }
};
