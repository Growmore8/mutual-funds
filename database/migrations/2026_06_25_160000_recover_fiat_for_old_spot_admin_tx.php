<?php

use App\Models\Deposit;
use App\Models\Withdrawal;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Old admin fiat spot deposits/withdrawals were stored as USD with the original
     * fiat only written into the `method` note, e.g. "Admin deposit · 26,750.00 INR".
     * Recover that fiat into the proper fields so the conversion rate displays.
     */
    public function up(): void
    {
        // amount per $1 isn't known; we keep the already-frozen USD (usd_amount/amount) and
        // restore currency + fiat amount from the note so "fiat @ rate/$" can render.
        $pattern = '/([\d,]+\.?\d*)\s+([A-Z]{3})\s*$/';

        foreach ([Deposit::class, Withdrawal::class] as $model) {
            $model::where('purpose', 'spot')
                ->where(fn ($q) => $q->whereNull('currency')->orWhere('currency', 'USD'))
                ->whereNotNull('method')
                ->where('method', 'like', '%·%')
                ->get()
                ->each(function ($row) use ($pattern) {
                    if (! preg_match($pattern, (string) $row->method, $m)) {
                        return;
                    }
                    $fiat = (float) str_replace(',', '', $m[1]);
                    $cur = $m[2];
                    if ($cur === 'USD' || $fiat <= 0) {
                        return;
                    }
                    // Keep the frozen USD value, then store the recovered fiat.
                    $usd = $row->usd_amount !== null ? (float) $row->usd_amount : (float) $row->amount;
                    $row->forceFill([
                        'usd_amount' => $usd,
                        'amount' => $fiat,
                        'currency' => $cur,
                    ])->saveQuietly();
                });
        }
    }

    public function down(): void
    {
        // One-way data recovery; nothing to revert.
    }
};
