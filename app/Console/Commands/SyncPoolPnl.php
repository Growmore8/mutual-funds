<?php

namespace App\Console\Commands;

use App\Models\PoolAccount;
use App\Models\PoolSnapshot;
use App\Services\PnlDistributor;
use App\Services\PoolApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SyncPoolPnl extends Command
{
    protected $signature = 'pool:sync {--date= : Snapshot date (Y-m-d), defaults to today}';

    protected $description = 'Fetch pool balance/PnL from the API, store a daily snapshot, and distribute PnL to clients';

    public function handle(PoolApiClient $api, PnlDistributor $distributor): int
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date'))->toDateString() : now()->toDateString();
        $this->info(($api->isLive() ? 'LIVE' : 'STUB') . " pool sync for {$date}");

        $failed = 0;

        foreach (PoolAccount::where('is_active', true)->get() as $pool) {
            try {
                $data = $api->snapshot($pool);
                $opening = (float) $pool->balance;

                $snapshot = PoolSnapshot::updateOrCreate(
                    ['pool_account_id' => $pool->id, 'snapshot_date' => $date],
                    [
                        'opening_balance' => $opening,
                        'closing_balance' => $data['balance'],
                        'pnl' => $data['pnl'],
                        'floating_pnl' => $data['floating'] ?? 0,
                        'pnl_pct' => $data['pnl_pct'],
                        'raw' => $data['raw'],
                    ]
                );

                $pool->update([
                    'balance' => $data['balance'],
                    'equity' => $data['equity'],
                    'floating_pnl' => $data['floating'] ?? 0,
                    'last_synced_at' => now(),
                ]);

                // Only realized (closed) P&L is distributed; floating is informational.
                $credited = $distributor->distribute($snapshot);
                $this->line("  {$pool->account_ref}: closed {$data['pnl']}, floating " . ($data['floating'] ?? 0) . " → distributed to {$credited} client(s)");
            } catch (\Throwable $e) {
                $failed++;
                $msg = $this->cubexError($e);
                \Illuminate\Support\Facades\Log::warning("pool:sync failed for {$pool->account_ref}: " . $e->getMessage());
                $this->line("  {$pool->account_ref}: SKIPPED — {$msg}");
            }
        }

        $this->info($failed ? "Pool sync finished with {$failed} account(s) skipped." : 'Pool sync complete.');

        return self::SUCCESS;
    }

    /** Turn an API/HTTP exception into a short human message. */
    private function cubexError(\Throwable $e): string
    {
        if ($e instanceof \Illuminate\Http\Client\RequestException && $e->response) {
            return match ($e->response->status()) {
                401 => 'CubeX rejected the API key (401)',
                404 => 'No such account on CubeX (404)',
                429 => 'CubeX rate limit hit (429) — try again shortly',
                default => 'CubeX error (HTTP ' . $e->response->status() . ')',
            };
        }

        return 'could not reach CubeX (' . class_basename($e) . ')';
    }
}
