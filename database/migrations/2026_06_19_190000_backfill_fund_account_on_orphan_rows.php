<?php

use App\Models\FundAccount;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Rows created after the multi-account rollout (e.g. admin-added transactions)
        // may have a null fund_account_id — attach them to the client's primary account.
        foreach (User::where('role', 'client')->get() as $u) {
            $primary = FundAccount::where('user_id', $u->id)
                ->orderByDesc('is_primary')->orderBy('id')->first();
            if (! $primary) {
                continue;
            }

            foreach (['transactions', 'deposits', 'withdrawals', 'pnl_allocations'] as $table) {
                DB::table($table)->where('user_id', $u->id)->whereNull('fund_account_id')
                    ->update(['fund_account_id' => $primary->id]);
            }
        }

        // Recompute each fund account's running balance so balance_after is per-account.
        foreach (FundAccount::all() as $acc) {
            $running = 0.0;
            Transaction::where('fund_account_id', $acc->id)->orderBy('id')->get()->each(function ($t) use (&$running) {
                $running = round($running + (float) $t->amount, 2);
                if ((float) $t->balance_after !== $running) {
                    $t->update(['balance_after' => $running]);
                }
            });
        }
    }

    public function down(): void
    {
        // No-op (data backfill).
    }
};
