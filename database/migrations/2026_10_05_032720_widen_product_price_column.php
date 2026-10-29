<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->decimal('price', 15, 2)->change();
        });
    }

    public function down(): void
    {
        if (DB::table('products')->where('price', '>', 99999999.99)->exists()) {
            throw new RuntimeException('Cannot reduce product price precision while products exceed the previous maximum.');
        }

        Schema::table('products', function (Blueprint $table): void {
            $table->decimal('price', 10, 2)->change();
        });
    }
};
