<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pool_accounts', function (Blueprint $table) {
            // Cumulative closed P&L already distributed — lets us pay out only
            // the newly-realized increment each sync (auto, intraday).
            $table->decimal('distributed_pnl', 15, 2)->default(0)->after('floating_pnl');
        });
    }

    public function down(): void
    {
        Schema::table('pool_accounts', fn (Blueprint $t) => $t->dropColumn('distributed_pnl'));
    }
};
