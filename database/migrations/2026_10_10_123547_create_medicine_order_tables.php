<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicine_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('appointment_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('requested');
            $table->timestamps();
        });

        Schema::create('medicine_order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('medicine_order_id')->constrained()->cascadeOnDelete();
            $table->string('medicine_name');
            $table->unsignedSmallInteger('quantity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_order_items');
        Schema::dropIfExists('medicine_orders');
    }
};
