<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->unsignedTinyInteger('patient_age')->nullable()->after('patient_id');
            $table->decimal('consultation_fee', 10, 2)->nullable()->after('ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropColumn(['patient_age', 'consultation_fee']);
        });
    }
};
