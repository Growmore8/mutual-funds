<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_types', function (Blueprint $table) {
            // The CubeX pool (Live ID) clients on this plan are assigned to.
            $table->foreignId('pool_account_id')->nullable()->after('pool_amount')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('account_types', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pool_account_id');
        });
    }
};
