<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('role', 'user')->update(['role' => 'customer']);
    }

    public function down(): void
    {
        // Legacy roles are intentionally not restored because new customer accounts may exist.
    }
};
