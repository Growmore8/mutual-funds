<?php

namespace App\Console\Commands;

use App\Models\Deposit;
use App\Models\PnlAllocation;
use App\Models\PoolSnapshot;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CheckDistribution extends Command
{
    protected $signature = 'pool:check {--date= : Y-m-d, defaults to today}';

    protected $description = 'Verify a day\'s PnL was distributed correctly to every client (pool net vs each share vs what was booked).';

    public function handle(): int
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date'))->toDateString() : now()->toDateString();
        $this->info("PnL distribution check for {$date}");
        $this->line('');

        $snapshots = PoolSnapshot::with('poolAccount')->whereDate('snapshot_date', $date)->get();

        if ($snapshots->isEmpty()) {
            $this->warn("No pool snapshots for {$date} — nothing was synced/distributed that day.");

            return self::SUCCESS;
        }

        foreach ($snapshots as $snap) {
            $ref = $snap->poolAccount->account_ref ?? ('pool#' . $snap->pool_account_id);
            $dayNet = (float) $snap->pnl;
            $this->line("── Pool {$ref}  ·  day net PnL: " . $this->fmt($dayNet) . "  ·  floating: " . $this->fmt((float) $snap->floating_pnl));

            // Clients invested in this pool as of the date.
            $rows = Deposit::query()
                ->join('users', 'users.id', '=', 'deposits.user_id')
                ->leftJoin('account_types', 'account_types.id', '=', 'users.account_type_id')
                ->where('deposits.pool_account_id', $snap->pool_account_id)
                ->where('deposits.status', 'approved')
                ->whereDate('deposits.value_date', '<=', $date)
                ->where('users.status', 'active')
                ->groupBy('deposits.user_id', 'users.name', 'account_types.pool_amount')
                ->selectRaw('deposits.user_id as uid, users.name as name, SUM(deposits.amount) as capital, account_types.pool_amount as pool_amount')
                ->get();

            if ($rows->isEmpty()) {
                $this->line('   (no clients invested in this pool)');
                $this->line('');
                continue;
            }

            $this->line(sprintf('   %-22s %10s %7s %12s %12s %12s %s', 'Client', 'Capital', 'Share', 'Expected', 'Booked(txn)', 'Allocation', ''));

            $sumExpected = 0;
            $sumBooked = 0;
            foreach ($rows as $r) {
                $poolAmount = (float) $r->pool_amount;
                $weight = $poolAmount > 0 ? min(1.0, (float) $r->capital / $poolAmount) : 0.0;
                $expected = round($dayNet * $weight, 2);

                $booked = round((float) Transaction::where('user_id', $r->uid)
                    ->where('type', 'profit')
                    ->whereDate('created_at', $date)
                    ->sum('amount'), 2);

                $alloc = round((float) PnlAllocation::where('user_id', $r->uid)
                    ->where('pool_snapshot_id', $snap->id)
                    ->sum('net_pnl'), 2);

                $flag = abs($expected - $booked) >= 0.02 ? '  <-- MISMATCH' : '  ok';

                $this->line(sprintf('   %-22s %10s %6s%% %12s %12s %12s%s',
                    substr($r->name, 0, 22),
                    number_format((float) $r->capital, 2),
                    rtrim(rtrim(number_format($weight * 100, 2), '0'), '.'),
                    $this->fmt($expected),
                    $this->fmt($booked),
                    $this->fmt($alloc),
                    $flag,
                ));

                $sumExpected += $expected;
                $sumBooked += $booked;
            }

            $this->line(sprintf('   %-22s %10s %7s %12s %12s', 'TOTAL', '', '', $this->fmt(round($sumExpected, 2)), $this->fmt(round($sumBooked, 2))));
            $this->line('');
        }

        $this->info('Legend: Expected = pool day net × client share. Booked = profit transactions that day (drives balance). Allocation = profit-history record.');
        $this->info('They should match. "MISMATCH" = booked balance differs from expected (e.g. a row was deleted/edited).');

        return self::SUCCESS;
    }

    private function fmt(float $n): string
    {
        return ($n < 0 ? '-' : '+') . '$' . number_format(abs($n), 2);
    }
}
