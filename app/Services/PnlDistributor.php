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

        // Eligible capital per client IN THIS POOL as of the snapshot date,
        // plus the client's plan pool_amount (the fixed share denominator).
        $rows = Deposit::query()
            ->join('users', 'users.id', '=', 'deposits.user_id')
            ->leftJoin('account_types', 'account_types.id', '=', 'users.account_type_id')
            ->where('deposits.pool_account_id', $poolId)
            ->where('deposits.status', 'approved')
            ->whereDate('deposits.value_date', '<=', $date)
            ->where('users.status', 'active')
            ->groupBy('deposits.user_id', 'account_types.pool_amount')
            ->selectRaw('deposits.user_id as user_id, SUM(deposits.amount) as capital, account_types.pool_amount as pool_amount')
            ->get();

        if ($rows->isEmpty()) {
            $snapshot->update(['distributed' => true]);
            return 0;
        }

        $count = 0;

        DB::transaction(function () use ($rows, $snapshot, $date, &$count) {
            foreach ($rows as $row) {
                $capital = (float) $row->capital;
                // Profit share = invested / plan pool amount (capped at 100%).
                $poolAmount = (float) $row->pool_amount;
                $weight = $poolAmount > 0 ? min(1.0, $capital / $poolAmount) : 0.0;
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

                if (abs($net) > 0) {
                    \App\Models\AppNotification::push(
                        $row->user_id,
                        'profit',
                        'Daily profit added',
                        ($net < 0 ? '-' : '+') . '$' . number_format(abs($net), 2) . ' · ' . $date->format('d M Y'),
                        route('client.profit'),
                    );
                }

                $count++;
            }

            $snapshot->update(['distributed' => true]);
        });

        return $count;
    }
}
