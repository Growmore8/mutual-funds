<?php

namespace App\Console\Commands;

use App\Services\SpotLiquiditySeeder;
use Illuminate\Console\Command;

class SpotSeed extends Command
{
    protected $signature = 'spot:seed';

    protected $description = 'Refresh seeded liquidity (house maker bids/asks) around the Twelve Data price';

    public function handle(SpotLiquiditySeeder $seeder): int
    {
        $n = $seeder->seedAll();
        $this->info("Seeded liquidity for {$n} instrument(s).");

        return self::SUCCESS;
    }
}
