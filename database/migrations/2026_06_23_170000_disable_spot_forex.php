<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Remove forex from Spot Trading (disable so the seeder/markets skip them).
        DB::table('spot_instruments')->where('market', 'forex')->update(['enabled' => false]);
    }

    public function down(): void
    {
        DB::table('spot_instruments')->where('market', 'forex')->update(['enabled' => true]);
    }
};
