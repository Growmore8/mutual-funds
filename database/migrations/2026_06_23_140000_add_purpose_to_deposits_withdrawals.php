<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 'fund' = mutual-fund pool (existing, default/unchanged). 'spot' = spot trading wallet.
        Schema::table('deposits', function (Blueprint $table) {
            $table->string('purpose')->default('fund')->after('status');
        });
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->string('purpose')->default('fund')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('deposits', fn (Blueprint $t) => $t->dropColumn('purpose'));
        Schema::table('withdrawals', fn (Blueprint $t) => $t->dropColumn('purpose'));
    }
};
