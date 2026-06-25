<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fund_accounts', function (Blueprint $table) {
            $table->boolean('locked')->default(false)->after('plan_locked');   // read-only: client can view, not act
            $table->boolean('active')->default(true)->after('locked');         // deactivated: account unusable
        });
    }

    public function down(): void
    {
        Schema::table('fund_accounts', function (Blueprint $table) {
            $table->dropColumn(['locked', 'active']);
        });
    }
};
