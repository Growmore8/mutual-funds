<?php

namespace App\Console\Commands;

use App\Models\PnlAllocation;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileBalances extends Command
{
    protected $signature = 'pool:reconcile {--user= : Only this client id}';

    protected $description = 'Align each client balance with their distributed PnL (allocations) — fixes profit/loss that did not reach the ledger.';

    public function handle(): int
    {
        $query = User::where('role', 'client');
        if ($this->option('user')) {
            $query->where('id', (int) $this->option('user'));
        }

        $fixed = 0;
        foreach ($query->get() as $user) {
            $allocSum = round((float) PnlAllocation::where('user_id', $user->id)->sum('net_pnl'), 2);
            $txnProfit = round((float) Transaction::where('user_id', $user->id)->where('type', 'profit')->sum('amount'), 2);
            $diff = round($allocSum - $txnProfit, 2);

            if (abs($diff) < 0.01) {
                $this->line("  {$user->clientCode()} {$user->name}: ok (profit {$txnProfit})");
                continue;
            }

            DB::transaction(function () use ($user, $diff) {
                $last = Transaction::where('user_id', $user->id)->latest('id')->first();
                Transaction::create([
                    'user_id' => $user->id,
                    'type' => 'profit',
                    'amount' => $diff,
                    'currency' => 'USD',
                    'balance_after' => round((float) ($last->balance_after ?? 0) + $diff, 2),
                    'status' => 'completed',
                    'description' => 'Profit reconciliation',
                ]);
                $this->recalc($user->id);
            });

            $fixed++;
            $this->line("  {$user->clientCode()} {$user->name}: corrected by " . ($diff < 0 ? '-' : '+') . '$' . number_format(abs($diff), 2) . " -> profit now {$allocSum}");
        }

        $this->info("Reconcile complete. {$fixed} client(s) corrected.");

        return self::SUCCESS;
    }

    private function recalc(int $userId): void
    {
        $running = 0.0;
        Transaction::where('user_id', $userId)->orderBy('id')->get()->each(function ($t) use (&$running) {
            $running = round($running + (float) $t->amount, 2);
            if ((float) $t->balance_after !== $running) {
                $t->update(['balance_after' => $running]);
            }
        });
    }
}
