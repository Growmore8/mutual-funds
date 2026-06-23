<?php

namespace App\Console\Commands;

use App\Services\SpotTradingService;
use Illuminate\Console\Command;

class SpotFund extends Command
{
    protected $signature = 'spot:fund {user} {amount}';

    protected $description = 'Credit (or debit, with a negative amount) a client spot wallet';

    public function handle(SpotTradingService $svc): int
    {
        $acc = $svc->adjustBalance((int) $this->argument('user'), (float) $this->argument('amount'));
        $this->info("User {$this->argument('user')} spot balance: {$acc->balance}");

        return self::SUCCESS;
    }
}
