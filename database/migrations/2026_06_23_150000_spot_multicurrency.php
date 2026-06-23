<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Instruments carry their native currency: Indian stocks = INR, everything else = USD.
        Schema::table('spot_instruments', function (Blueprint $table) {
            $table->string('currency', 8)->default('USD')->after('market');
        });
        DB::table('spot_instruments')->where('market', 'india')->update(['currency' => 'INR']);

        // Spot wallets become per-currency (a user has one wallet per currency).
        Schema::table('spot_accounts', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
            $table->unique(['user_id', 'currency']);
        });
    }

    public function down(): void
    {
        Schema::table('spot_accounts', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'currency']);
            $table->unique(['user_id']);
        });
        Schema::table('spot_instruments', fn (Blueprint $t) => $t->dropColumn('currency'));
    }
};
