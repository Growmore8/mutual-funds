<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // When true, the client's plan + Live ID are set manually by admin
            // and the auto deposit-based recalculation will not change them.
            $table->boolean('plan_locked')->default(false)->after('pool_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('plan_locked'));
    }
};
