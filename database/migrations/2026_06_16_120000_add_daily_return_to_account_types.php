<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_types', function (Blueprint $table) {
            // Headline daily return %. Daily profit cap = pool_amount * pct / 100.
            $table->decimal('daily_return_pct', 6, 2)->default(0)->after('pool_amount');
        });
    }

    public function down(): void
    {
        Schema::table('account_types', fn (Blueprint $t) => $t->dropColumn('daily_return_pct'));
    }
};
