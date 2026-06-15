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

        foreach (PoolAccount::where('is_active', true)->get() as $pool) {
            $data = $api->snapshot($pool);
            $opening = (float) $pool->balance;

            $snapshot = PoolSnapshot::updateOrCreate(
                ['pool_account_id' => $pool->id, 'snapshot_date' => $date],
                [
                    'opening_balance' => $opening,
                    'closing_balance' => $data['balance'],
                    'pnl' => $data['pnl'],
                    'pnl_pct' => $data['pnl_pct'],
                    'raw' => $data['raw'],
                ]
            );

            $pool->update([
                'balance' => $data['balance'],
                'equity' => $data['equity'],
                'last_synced_at' => now(),
            ]);

            $credited = $distributor->distribute($snapshot);
            $this->line("  {$pool->account_ref}: PnL {$data['pnl']} → distributed to {$credited} client(s)");
        }

        $this->info('Pool sync complete.');

        return self::SUCCESS;
    }
}
