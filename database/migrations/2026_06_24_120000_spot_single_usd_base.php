<?php

use App\Models\SpotAccount;
use App\Models\SpotHolding;
use App\Models\SpotInstrument;
use App\Services\SpotTradingService;
use Illuminate\Database\Migrations\Migration;

/**
 * Move the spot module to a single USD base.
 * Existing INR wallet balances and INR holdings' average prices are converted to USD
 * at the live rate, then INR wallets are merged into the USD wallet.
 */
return new class extends Migration
{
    public function up(): void
    {
        $rate = app(SpotTradingService::class)->usdInr(); // 1 USD = $rate INR
        if ($rate <= 0) {
            $rate = 84.0;
        }

        // 1) Convert INR holdings' avg_price to USD (last_price will be reseeded in USD).
        $inrIds = SpotInstrument::where('currency', 'INR')->pluck('id');
        foreach (SpotHolding::whereIn('instrument_id', $inrIds)->get() as $h) {
            $h->avg_price = round((float) $h->avg_price / $rate, 6);
            $h->save();
        }

        // 2) Merge each INR wallet into the user's USD wallet (converted), then zero the INR wallet.
        foreach (SpotAccount::where('currency', 'INR')->get() as $inr) {
            $usdValue = round((float) $inr->balance / $rate, 2);
            if ($usdValue != 0.0) {
                $usd = SpotAccount::firstOrCreate(['user_id' => $inr->user_id, 'currency' => 'USD'], ['balance' => 0]);
                $usd->increment('balance', $usdValue);
            }
            $inr->update(['balance' => 0]);
        }
    }

    public function down(): void
    {
        // Irreversible (rate-dependent) — no-op.
    }
};
