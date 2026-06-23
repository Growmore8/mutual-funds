<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Spot Trading = US/Global/Crypto + India only. Remove commodity (and keep forex disabled).
        DB::table('spot_instruments')->whereIn('market', ['commodity', 'forex'])->update(['enabled' => false]);
    }

    public function down(): void
    {
        DB::table('spot_instruments')->where('market', 'commodity')->update(['enabled' => true]);
    }
};
