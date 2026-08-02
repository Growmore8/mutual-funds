<?php

namespace App\Console\Commands;

use App\Services\CubexMarketClient;
use Illuminate\Console\Command;

class SpotCheck extends Command
{
    protected $signature = 'spot:check {symbol=EURUSD}';

    protected $description = 'Verify the market-data API key and fetch a sample price + candles';

    public function handle(CubexMarketClient $cubex): int
    {
        if (! $cubex->configured()) {
            $this->error('Market data API is not configured (set CUBEX_PRICES_URL and CUBEX_API_KEY in .env).');

            return self::FAILURE;
        }

        $symbol = strtoupper(str_replace('/', '', $this->argument('symbol')));

        $this->info("Fetching price for {$symbol} …");
        $price = $cubex->prices([$symbol])[$symbol] ?? null;

        if ($price === null) {
            $this->error('No price returned — check the API key and symbol.');

            return self::FAILURE;
        }

        $this->line('Price:   ' . $price);

        $candles = $cubex->candles($symbol, '1h', 3);
        $this->line('Candles: ' . count($candles) . ' bars (1h)');
        if ($candles) {
            $last = end($candles);
            $this->line('Last:    O ' . $last['open'] . ' H ' . $last['high'] . ' L ' . $last['low'] . ' C ' . $last['close']);
        }

        $this->info('Market data API key is working. ✅');

        return self::SUCCESS;
    }
}
