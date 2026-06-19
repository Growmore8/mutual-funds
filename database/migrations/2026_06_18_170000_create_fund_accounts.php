<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fund_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->foreignId('account_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pool_account_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('plan_locked')->default(false);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        foreach (['deposits', 'withdrawals', 'transactions', 'pnl_allocations'] as $tbl) {
            if (Schema::hasTable($tbl) && ! Schema::hasColumn($tbl, 'fund_account_id')) {
                Schema::table($tbl, function (Blueprint $table) {
                    $table->foreignId('fund_account_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
                });
            }
        }

        // Backfill: each existing user gets one primary account holding all their current data.
        foreach (DB::table('users')->where('role', 'client')->get() as $u) {
            $id = DB::table('fund_accounts')->insertGetId([
                'user_id' => $u->id,
                'label' => 'Account 1',
                'account_type_id' => $u->account_type_id,
                'pool_account_id' => $u->pool_account_id,
                'plan_locked' => $u->plan_locked ?? false,
                'is_primary' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach (['deposits', 'withdrawals', 'transactions', 'pnl_allocations'] as $tbl) {
                DB::table($tbl)->where('user_id', $u->id)->update(['fund_account_id' => $id]);
            }
        }
    }

    public function down(): void
    {
        foreach (['deposits', 'withdrawals', 'transactions', 'pnl_allocations'] as $tbl) {
            if (Schema::hasColumn($tbl, 'fund_account_id')) {
                Schema::table($tbl, fn (Blueprint $t) => $t->dropConstrainedForeignId('fund_account_id'));
            }
        }
        Schema::dropIfExists('fund_accounts');
    }
};
