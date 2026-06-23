<?php

namespace App\Console\Commands;

use App\Models\SpotHolding;
use App\Models\SpotInstrument;
use App\Services\SpotTradingService;
use Illuminate\Console\Command;

class SpotOrderCmd extends Command
{
    protected $signature = 'spot:order {user} {symbol} {side} {qty} {--type=market} {--price=}';

    protected $description = 'Place a spot order (test): spot:order 2 RELIANCE buy 5 --type=market';

    public function handle(SpotTradingService $svc): int
    {
        $ins = SpotInstrument::where('symbol', $this->argument('symbol'))->first();
        if (! $ins) {
            $this->error('Unknown symbol. Seed/instruments first.');

            return self::FAILURE;
        }

        try {
            $order = $svc->placeOrder(
                (int) $this->argument('user'), $ins, $this->argument('side'),
                $this->option('type'), $this->option('price') ? (float) $this->option('price') : null,
                (float) $this->argument('qty'),
            );
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Order #{$order->id} {$order->side} {$order->qty} {$ins->symbol} -> status {$order->status}, filled {$order->filled_qty}");
        $bal = $svc->account((int) $this->argument('user'))->balance;
        $hold = SpotHolding::where('user_id', $this->argument('user'))->where('instrument_id', $ins->id)->first();
        $this->line("Balance: {$bal} | Holding: " . ($hold ? $hold->qty . ' @ ' . $hold->avg_price : '0'));

        return self::SUCCESS;
    }
}
