<?php

namespace App\Services;

use App\Models\Deposit;
use App\Models\PnlAllocation;
use App\Models\PoolSnapshot;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

/**
 * Distributes a pool's daily PnL to the clients invested in THAT pool, in
 * proportion to their eligible capital (approved deposits placed in that pool
 * whose value/joining date is on/before the snapshot date).
 *
 * Because this runs per day, a later joiner automatically only earns from the
 * day they joined — i.e. profit is weighted by both deposit amount and joining
 * date. Clients receive 100% of their proportional share.
 */
class PnlDistributor
{
    public function distribute(PoolSnapshot $snapshot): int
    {
        if ($snapshot->distributed) {
            return 0;
        }

        $date = $snapshot->snapshot_date;
        $poolId = $snapshot->pool_account_id;

        // Eligible capital per client IN THIS POOL as of the snapshot date.
        $rows = Deposit::query()
            ->where('pool_account_id', $poolId)
            ->where('status', 'approved')
            ->whereDate('value_date', '<=', $date)
            ->whereHas('user', fn ($q) => $q->where('status', 'active'))
            ->selectRaw('user_id, SUM(amount) as capital')
            ->groupBy('user_id')
            ->get();

        $totalCapital = (float) $rows->sum('capital');

        if ($totalCapital <= 0) {
            $snapshot->update(['distributed' => true]);
            return 0;
        }

        $count = 0;

        DB::transaction(function () use ($rows, $totalCapital, $snapshot, $date, &$count) {
            foreach ($rows as $row) {
                $capital = (float) $row->capital;
                $weight = $capital / $totalCapital;                 // client's share of this pool
                $net = round((float) $snapshot->pnl * $weight, 2);   // 100% of their share

                PnlAllocation::updateOrCreate(
                    ['pool_snapshot_id' => $snapshot->id, 'user_id' => $row->user_id],
                    [
                        'allocation_date' => $date,
                        'eligible_capital' => $capital,
                        'weight' => $weight,
                        'gross_pnl' => $net,
                        'fee' => 0,
                        'net_pnl' => $net,
                    ]
                );

                $last = Transaction::where('user_id', $row->user_id)->latest('id')->first();
                $balanceAfter = round((float) ($last->balance_after ?? 0) + $net, 2);

                Transaction::create([
                    'user_id' => $row->user_id,
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
