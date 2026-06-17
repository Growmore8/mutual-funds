<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixPoolShares extends Command
{
    protected $signature = 'pool:fix-shares {--apply : Actually write the corrections (otherwise dry-run)} {--user= : Only this client id}';

    protected $description = 'Set each client\'s total profit to their correct share of the pool\'s cumulative realized PnL (capital ÷ pool_amount × pool.distributed_pnl).';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $this->info($apply ? 'APPLYING corrections…' : 'DRY RUN (add --apply to write). Nothing changed yet.');
        $this->line('');

        $query = User::where('role', 'client')->with('accountType', 'poolAccount');
        if ($this->option('user')) {
            $query->where('id', (int) $this->option('user'));
        }

        $fixed = 0;
        foreach ($query->get() as $user) {
            $pool = $user->poolAccount;
            $at = $user->accountType;
            if (! $pool || ! $at) {
                continue;
            }

            $poolAmount = (float) ($at->pool_amount ?: $pool->capacity);
            $capital = $user->totalDeposited();
            $weight = $poolAmount > 0 ? min(1.0, $capital / $poolAmount) : 0.0;
            $should = round((float) $pool->distributed_pnl * $weight, 2);
            $actual = round((float) Transaction::where('user_id', $user->id)->where('type', 'profit')->sum('amount'), 2);
            $diff = round($should - $actual, 2);

            if (abs($diff) < 0.01) {
                $this->line("  {$user->clientCode()} {$user->name}: ok ({$actual})");
                continue;
            }

            $this->line("  {$user->clientCode()} {$user->name}: {$actual} -> {$should}  (adjust " . ($diff < 0 ? '' : '+') . "{$diff})");

            if ($apply) {
                DB::transaction(function () use ($user, $diff) {
                    $last = Transaction::where('user_id', $user->id)->latest('id')->first();
                    Transaction::create([
                        'user_id' => $user->id,
                        'type' => 'profit',
                        'amount' => $diff,
                        'currency' => 'USD',
                        'balance_after' => round((float) ($last->balance_after ?? 0) + $diff, 2),
                        'status' => 'completed',
                        'description' => 'PnL correction',
                    ]);
                    $running = 0.0;
                    Transaction::where('user_id', $user->id)->orderBy('id')->get()->each(function ($t) use (&$running) {
                        $running = round($running + (float) $t->amount, 2);
                        if ((float) $t->balance_after !== $running) {
                            $t->update(['balance_after' => $running]);
                        }
                    });
                });
            }

            $fixed++;
        }

        $this->line('');
        $this->info($apply ? "Done. {$fixed} client(s) corrected." : "{$fixed} client(s) WOULD be corrected. Re-run with --apply to write.");

        return self::SUCCESS;
    }
}
