<?php

use App\Models\AccountRequest;
use App\Models\FundAccount;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Each fund account gets its own unique account number.
        if (! Schema::hasColumn('fund_accounts', 'account_no')) {
            Schema::table('fund_accounts', function (Blueprint $table) {
                $table->string('account_no')->nullable()->unique()->after('id');
            });
        }

        // 2) Backfill existing accounts with a clean, contiguous number (GCA000001…).
        $seq = 0;
        foreach (FundAccount::orderBy('id')->get() as $acc) {
            if (empty($acc->account_no)) {
                $seq++;
                $acc->forceFill(['account_no' => 'GCA' . str_pad((string) $seq, 6, '0', STR_PAD_LEFT)])->saveQuietly();
            } else {
                $n = (int) substr($acc->account_no, 3);
                $seq = max($seq, $n);
            }
        }

        // 3) Link account requests to the account they create (for future approvals).
        if (Schema::hasTable('account_requests') && ! Schema::hasColumn('account_requests', 'fund_account_id')) {
            Schema::table('account_requests', function (Blueprint $table) {
                $table->foreignId('fund_account_id')->nullable()->after('account_type_id');
            });
        }

        // 4) Reconcile: ensure each client has (1 primary + each approved request) accounts.
        //    Older approvals (before the per-account feature) never created an account.
        foreach (User::where('role', 'client')->get() as $u) {
            $approved = AccountRequest::where('user_id', $u->id)
                ->where('status', 'approved')
                ->orderBy('id')
                ->get();

            $expected = 1 + $approved->count();          // primary + one per approved request
            $have = FundAccount::where('user_id', $u->id)->count();
            $missing = max(0, $expected - $have);

            for ($i = 0; $i < $missing; $i++) {
                // Use the matching approved request (the last $missing requests) for plan/pool.
                $req = $approved->get($approved->count() - $missing + $i);
                $count = FundAccount::where('user_id', $u->id)->count();

                $acc = FundAccount::create([
                    'user_id' => $u->id,
                    'label' => 'Account ' . ($count + 1),
                    'account_type_id' => $req?->account_type_id,
                    'pool_account_id' => $req ? optional($req->accountType)->pool_account_id : null,
                    'is_primary' => false,
                ]);

                if ($req && empty($req->fund_account_id)) {
                    $req->forceFill(['fund_account_id' => $acc->id])->save();
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('account_requests', 'fund_account_id')) {
            Schema::table('account_requests', function (Blueprint $table) {
                $table->dropColumn('fund_account_id');
            });
        }
        if (Schema::hasColumn('fund_accounts', 'account_no')) {
            Schema::table('fund_accounts', function (Blueprint $table) {
                $table->dropUnique(['account_no']);
                $table->dropColumn('account_no');
            });
        }
    }
};
