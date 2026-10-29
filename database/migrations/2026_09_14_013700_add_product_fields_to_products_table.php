<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the product fields when the existing database was created
     * from an incomplete version of the products migration.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'name')) {
                $table->string('name')->default('');
            }

            if (! Schema::hasColumn('products', 'description')) {
                $table->text('description')->nullable();
            }

            if (! Schema::hasColumn('products', 'price')) {
                $table->decimal('price', 10, 2)->default(0);
            }

            if (! Schema::hasColumn('products', 'stock')) {
                $table->integer('stock')->default(0);
            }
        });
    }

    public function down(): void
    {
        // The columns may already belong to the original products migration.
    }
};
