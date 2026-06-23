<?php

use App\Models\Deposit;
use App\Models\SpotAccount;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Create an "Opening balance" record for spot wallets credited before
        // admin adjustments started writing transaction records.
        foreach (SpotAccount::where('balance', '>', 0)->get() as $acc) {
            $hasRecord = Deposit::where('user_id', $acc->user_id)
                ->where('purpose', 'spot')->where('currency', $acc->currency)->exists();

            if (! $hasRecord) {
                Deposit::create([
                    'user_id' => $acc->user_id,
                    'purpose' => 'spot',
                    'currency' => $acc->currency,
                    'amount' => $acc->balance,
                    'method' => 'Opening balance',
                    'status' => 'approved',
                    'value_date' => now()->toDateString(),
                    'approved_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // no-op
    }
};
