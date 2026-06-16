<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pool_accounts', function (Blueprint $table) {
            $table->decimal('floating_pnl', 15, 2)->default(0)->after('equity');
        });

        Schema::table('pool_snapshots', function (Blueprint $table) {
            $table->decimal('floating_pnl', 15, 2)->default(0)->after('pnl');
        });
    }

    public function down(): void
    {
        Schema::table('pool_accounts', fn (Blueprint $t) => $t->dropColumn('floating_pnl'));
        Schema::table('pool_snapshots', fn (Blueprint $t) => $t->dropColumn('floating_pnl'));
    }
};
