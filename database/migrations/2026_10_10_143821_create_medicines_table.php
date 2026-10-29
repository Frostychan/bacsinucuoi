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
        Schema::create('medicines', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('strength', 100)->nullable();
            $table->string('unit', 50)->nullable();
            $table->decimal('price', total: 12, places: 2)->nullable();
            $table->timestamps();
        });

        $now = now();
        $names = [
            'Paracetamol',
            'Ibuprofen',
            'Amoxicillin',
            'Azithromycin',
            'Cefuroxime',
            'Metronidazole',
            'Omeprazole',
            'Esomeprazole',
            'Loratadine',
            'Cetirizine',
            'Amlodipine',
            'Losartan',
            'Metformin',
            'Atorvastatin',
            'Aspirin',
            'Vitamin C',
            'Calcium carbonate',
            'Oresol (ORS)',
            'Salbutamol',
            'Guaifenesin',
        ];
        $medicines = array_map(
            fn (string $name): array => [
                'name' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $names,
        );

        DB::table('medicines')->insert($medicines);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicines');
    }
};
