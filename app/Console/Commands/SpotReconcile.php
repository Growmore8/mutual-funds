<?php

namespace App\Console\Commands;

use App\Models\Deposit;
use App\Models\SpotAccount;
use App\Models\SpotHolding;
use App\Models\SpotTrade;
use App\Models\Withdrawal;
use Illuminate\Console\Command;

class SpotReconcile extends Command
{
    protected $signature = 'spot:reconcile {--apply : actually write the fixes (default is a dry run)}';

    protected $description = 'Reconcile spot "capital in" so P&L equals the real trade-ledger profit (fixes pre-fix INR-deposit FX drift / phantom cents).';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $userIds = Deposit::where('purpose', 'spot')->distinct()->pluck('user_id');
        $fixed = 0;

        foreach ($userIds as $uid) {
            $wallet = (float) SpotAccount::where('user_id', $uid)->where('currency', 'USD')->value('balance');

            // Replay trades (average-cost) to get realised P&L exactly as the ledger produced it.
            $realized = 0.0;
            $pos = [];
            foreach (SpotTrade::where(fn ($q) => $q->where('buyer_id', $uid)->orWhere('seller_id', $uid))->orderBy('id')->get() as $t) {
                $iid = $t->instrument_id;
                $p = (float) $t->price;
                $qy = (float) $t->qty;
                $pos[$iid] ??= ['qty' => 0.0, 'avg' => 0.0];
                if ($t->buyer_id === $uid) {
                    $nq = $pos[$iid]['qty'] + $qy;
                    $pos[$iid]['avg'] = $nq > 0 ? (($pos[$iid]['qty'] * $pos[$iid]['avg']) + $qy * $p) / $nq : 0;
                    $pos[$iid]['qty'] = $nq;
                } else {
                    $realized += $qy * ($p - $pos[$iid]['avg']);
                    $pos[$iid]['qty'] = max(0, $pos[$iid]['qty'] - $qy);
                    if ($pos[$iid]['qty'] <= 1e-9) {
                        $pos[$iid]['avg'] = 0;
                    }
                }
            }

            $holdingsCost = (float) SpotHolding::where('user_id', $uid)->where('qty', '>', 0)->get()
                ->sum(fn ($h) => (float) $h->qty * (float) $h->avg_price);

            // capital-in that makes total P&L == realised + unrealised (the truth).
            $capitalTrue = round($wallet + $holdingsCost - $realized, 2);

            $netDep = round(
                (float) Deposit::where('user_id', $uid)->where('purpose', 'spot')->where('status', 'approved')->sum('usd_amount')
                - (float) Withdrawal::where('user_id', $uid)->where('purpose', 'spot')->where('status', 'approved')->sum('usd_amount'),
                2
            );

            $delta = round($capitalTrue - $netDep, 2);
            if (abs($delta) < 0.01) {
                continue;
            }

            // Correct the drift on a non-USD (INR) deposit if present, else the latest deposit.
            $dep = Deposit::where('user_id', $uid)->where('purpose', 'spot')->where('status', 'approved')->where('currency', '!=', 'USD')->latest('id')->first()
                ?? Deposit::where('user_id', $uid)->where('purpose', 'spot')->where('status', 'approved')->latest('id')->first();

            $this->line("user {$uid}: wallet {$wallet} realised " . round($realized, 2) . " capitalTrue {$capitalTrue} netDep {$netDep} delta {$delta}" . ($dep ? " -> dep#{$dep->id}" : ' (no deposit to adjust)'));

            if ($apply && $dep) {
                $dep->update(['usd_amount' => round((float) $dep->usd_amount + $delta, 2)]);
                $fixed++;
            }
        }

        $this->info($apply ? "Applied fixes to {$fixed} account(s)." : 'DRY RUN — re-run with --apply to write the fixes.');

        return self::SUCCESS;
    }
}
