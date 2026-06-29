<?php

namespace App\Console\Commands;

use App\Services\SpotLiquiditySeeder;
use Illuminate\Console\Command;

class SpotSeed extends Command
{
    protected $signature = 'spot:seed';

    protected $description = 'Refresh seeded liquidity (house maker bids/asks) around the Twelve Data price';

    public function handle(SpotLiquiditySeeder $seeder, \App\Services\SpotTradingService $fx): int
    {
        $fx->refreshFx();   // warm USD/INR (network) on the CLI so web pages never block on it
        $n = $seeder->seedAll();
        $this->info("Seeded liquidity for {$n} instrument(s).");

        return self::SUCCESS;
    }
}
