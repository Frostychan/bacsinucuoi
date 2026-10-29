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
        Schema::create('medicine_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('medicine_order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider_transaction_id', 100)->unique('sepay_txn_unique');
            $table->string('reference', 100)->nullable();
            $table->unsignedBigInteger('transfer_amount');
            $table->string('receiving_account', 50)->nullable();
            $table->string('status', 30);
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicine_payments');
    }
};
