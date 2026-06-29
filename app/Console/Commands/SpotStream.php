<?php

namespace App\Console\Commands;

use App\Services\SpotLiquiditySeeder;
use Illuminate\Console\Command;

class SpotStream extends Command
{
    protected $signature = 'spot:stream {--seconds=55} {--every=5}';

    protected $description = 'Stream live spot prices into the DB every few seconds (sub-minute live prices, off the web path).';

    public function handle(SpotLiquiditySeeder $seeder): int
    {
        $end = now()->addSeconds((int) $this->option('seconds'));
        $every = max(2, (int) $this->option('every'));

        while (now()->lt($end)) {
            $start = microtime(true);
            try {
                $seeder->refreshPrices();
            } catch (\Throwable $e) {
                // keep streaming even if one cycle fails
            }
            $elapsed = microtime(true) - $start;
            $sleep = $every - $elapsed;
            if ($sleep > 0 && now()->lt($end)) {
                usleep((int) ($sleep * 1_000_000));
            }
        }

        return self::SUCCESS;
    }
}
