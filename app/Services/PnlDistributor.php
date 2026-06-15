<?php

namespace App\Services;

use App\Models\PnlAllocation;
use App\Models\PoolSnapshot;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Distributes a day's pool PnL to clients in proportion to their eligible
 * capital (approved deposits whose value date is on/before the snapshot date —
 * i.e. their joining date). Each client's share is adjusted by their account
 * type's profit-share %, and credited as a `profit` transaction.
 */
class PnlDistributor
{
    public function distribute(PoolSnapshot $snapshot): int
    {
        if ($snapshot->distributed) {
            return 0;
        }

        $date = $snapshot->snapshot_date;

        // Eligible capital per active client as of the snapshot date.
        $clients = User::where('role', 'client')
            ->where('status', 'active')
            ->with('accountType')
            ->get()
            ->map(function (User $u) use ($date) {
                $capital = (float) $u->deposits()
                    ->where('status', 'approved')
                    ->whereDate('value_date', '<=', $date)
                    ->sum('amount');

                return ['user' => $u, 'capital' => $capital];
            })
            ->filter(fn ($r) => $r['capital'] > 0)
            ->values();

        $totalCapital = $clients->sum('capital');

        if ($totalCapital <= 0) {
            $snapshot->update(['distributed' => true]);
            return 0;
        }

        $count = 0;

        DB::transaction(function () use ($clients, $totalCapital, $snapshot, $date, &$count) {
            foreach ($clients as $row) {
                /** @var User $user */
                $user = $row['user'];
                $weight = $row['capital'] / $totalCapital;
                $gross = round((float) $snapshot->pnl * $weight, 2);
                $sharePct = (float) ($user->accountType->profit_share_pct ?? 100);
                $net = round($gross * $sharePct / 100, 2);
                $fee = round($gross - $net, 2);

                PnlAllocation::updateOrCreate(
                    ['pool_snapshot_id' => $snapshot->id, 'user_id' => $user->id],
                    [
                        'allocation_date' => $date,
                        'eligible_capital' => $row['capital'],
                        'weight' => $weight,
                        'gross_pnl' => $gross,
                        'fee' => $fee,
                        'net_pnl' => $net,
                    ]
                );

                $last = Transaction::where('user_id', $user->id)->latest('id')->first();
                $balanceAfter = round((float) ($last->balance_after ?? 0) + $net, 2);

                Transaction::create([
                    'user_id' => $user->id,
                    'type' => 'profit',
                    'amount' => $net,
                    'currency' => 'USD',
                    'balance_after' => $balanceAfter,
                    'status' => 'completed',
                    'description' => 'Daily profit · ' . $date->format('d M Y'),
                ]);

                $count++;
            }

            $snapshot->update(['distributed' => true]);
        });

        return $count;
    }
}
