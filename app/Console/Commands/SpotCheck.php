<?php

namespace App\Console\Commands;

use App\Services\TwelveDataClient;
use Illuminate\Console\Command;

class SpotCheck extends Command
{
    protected $signature = 'spot:check {symbol=RELIANCE} {--exchange=NSE}';

    protected $description = 'Verify the Twelve Data spot key and fetch a sample quote (Phase 1 sanity check)';

    public function handle(TwelveDataClient $td): int
    {
        if (! $td->configured()) {
            $this->error('TWELVEDATA_SPOT_KEY is not set in .env');

            return self::FAILURE;
        }

        $symbol = $this->argument('symbol');
        $exchange = $this->option('exchange') ?: null;

        $this->info("Fetching quote for {$symbol}" . ($exchange ? " ({$exchange})" : '') . ' …');
        $q = $td->quote($symbol, $exchange);

        if (! $q) {
            $this->error('No data returned — check the key, symbol, exchange, or plan coverage.');

            return self::FAILURE;
        }

        $this->line('Name:   ' . ($q['name'] ?? '—'));
        $this->line('Price:  ' . ($q['close'] ?? $q['price'] ?? '—'));
        $this->line('Change: ' . ($q['percent_change'] ?? '—') . '%');
        $this->line('Exch:   ' . ($q['exchange'] ?? '—'));
        $this->info('Twelve Data spot key is working. ✅');

        return self::SUCCESS;
    }
}
