<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('medical_record_medications')) {
            Schema::create('medical_record_medications', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('medical_record_id')->constrained()->cascadeOnDelete();
                $table->string('medicine_name');
                $table->unsignedSmallInteger('quantity');
                $table->timestamps();

                $table->unique(['medical_record_id', 'medicine_name'], 'record_medication_unique');
            });
        } elseif (! Schema::hasIndex('medical_record_medications', ['medical_record_id', 'medicine_name'], 'unique')) {
            Schema::table('medical_record_medications', function (Blueprint $table): void {
                $table->unique(['medical_record_id', 'medicine_name'], 'record_medication_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_record_medications');
    }
};
