<?php

use App\Models\Deposit;
use App\Models\SpotAccount;
use App\Models\SpotTrade;
use App\Models\Withdrawal;
use App\Services\SpotTradingService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Freeze the exact USD value credited/debited for spot deposits & withdrawals,
     * so "capital in" is no longer re-converted from INR at the live rate (which
     * created phantom P&L even with no trades).
     */
    public function up(): void
    {
        Schema::table('deposits', fn (Blueprint $t) => $t->decimal('usd_amount', 18, 2)->nullable()->after('amount'));
        Schema::table('withdrawals', fn (Blueprint $t) => $t->decimal('usd_amount', 18, 2)->nullable()->after('amount'));

        $svc = app(SpotTradingService::class);

        // 1) Freeze each spot row to its USD value.
        foreach (Deposit::where('purpose', 'spot')->get() as $d) {
            $d->usd_amount = round($svc->toUsd((float) $d->amount, $d->currency ?: 'USD'), 2);
            $d->saveQuietly();
        }
        foreach (Withdrawal::where('purpose', 'spot')->get() as $w) {
            $w->usd_amount = round($svc->toUsd((float) $w->amount, $w->currency ?: 'USD'), 2);
            $w->saveQuietly();
        }

        // 2) True-up accounts with NO trades: their P&L must be 0, so capital-in == wallet.
        $userIds = Deposit::where('purpose', 'spot')->distinct()->pluck('user_id');
        foreach ($userIds as $uid) {
            if (SpotTrade::where('buyer_id', $uid)->orWhere('seller_id', $uid)->exists()) {
                continue;
            }
            $wallet = (float) SpotAccount::where('user_id', $uid)->where('currency', 'USD')->value('balance');
            $sumDep = (float) Deposit::where('user_id', $uid)->where('purpose', 'spot')->where('status', 'approved')->sum('usd_amount');
            $sumWd = (float) Withdrawal::where('user_id', $uid)->where('purpose', 'spot')->where('status', 'approved')->sum('usd_amount');
            $delta = round($wallet - ($sumDep - $sumWd), 2);
            if (abs($delta) >= 0.01) {
                $last = Deposit::where('user_id', $uid)->where('purpose', 'spot')->where('status', 'approved')->latest('id')->first();
                if ($last) {
                    $last->usd_amount = round((float) $last->usd_amount + $delta, 2);
                    $last->saveQuietly();
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('deposits', fn (Blueprint $t) => $t->dropColumn('usd_amount'));
        Schema::table('withdrawals', fn (Blueprint $t) => $t->dropColumn('usd_amount'));
    }
};
