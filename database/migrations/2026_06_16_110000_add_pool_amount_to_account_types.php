<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_types', function (Blueprint $table) {
            // Fixed pool size for the plan; profit share = invested / pool_amount.
            $table->decimal('pool_amount', 15, 2)->default(0)->after('max_deposit');
        });
    }

    public function down(): void
    {
        Schema::table('account_types', fn (Blueprint $t) => $t->dropColumn('pool_amount'));
    }
};
