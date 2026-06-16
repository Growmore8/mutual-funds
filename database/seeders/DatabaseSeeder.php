<?php

namespace Database\Seeders;

use App\Models\AccountType;
use App\Models\PaymentMethod;
use App\Models\PoolAccount;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin account
        User::updateOrCreate(
            ['email' => 'admin@growthcapitalltd.com'],
            [
                'name' => 'Fund Admin',
                'password' => Hash::make('ChangeMe!2026'),
                'role' => 'admin',
                'status' => 'active',
                'kyc_status' => 'approved',
                'email_verified_at' => now(),
                'otp_verified_at' => now(),
            ]
        );

        // Mutual-fund plans (manageable from the admin dashboard)
        $types = [
            ['name' => 'Silver Plan',   'slug' => 'silver',   'min_deposit' => 50,  'max_deposit' => 250,  'pool_amount' => 10000, 'profit_share_pct' => 100, 'management_fee_pct' => 0, 'lock_in_months' => 1, 'sort_order' => 1, 'description' => 'Up to $500/day · ~5% daily return',   'features' => ['$10,000 managed pool', 'Up to $500/day profit', '~5% daily return', 'Funds stay client-owned']],
            ['name' => 'Gold Plan',     'slug' => 'gold',     'min_deposit' => 250, 'max_deposit' => 500,  'pool_amount' => 25000, 'profit_share_pct' => 100, 'management_fee_pct' => 0, 'lock_in_months' => 1, 'sort_order' => 2, 'description' => 'Up to $1,500/day · ~6% daily return', 'features' => ['$25,000 managed pool', 'Up to $1,500/day profit', '~6% daily return', 'Funds stay client-owned']],
            ['name' => 'Platinum Plan', 'slug' => 'platinum', 'min_deposit' => 500, 'max_deposit' => 2500, 'pool_amount' => 50000, 'profit_share_pct' => 100, 'management_fee_pct' => 0, 'lock_in_months' => 1, 'sort_order' => 3, 'description' => 'Up to $4,000/day · ~8% daily return', 'features' => ['$50,000 managed pool', 'Up to $4,000/day profit', '~8% daily return', 'Funds stay client-owned']],
        ];
        foreach ($types as $t) {
            AccountType::updateOrCreate(['slug' => $t['slug']], $t + ['is_active' => true]);
        }

        // Hide any older/demo plans from the app + marketing page (kept for existing clients).
        AccountType::whereNotIn('slug', ['silver', 'gold', 'platinum'])->update(['is_active' => false]);

        // Payment methods (manageable from the admin dashboard)
        $methods = [
            ['name' => 'Bank Wire',      'type' => 'bank',   'currency' => 'USD',  'sort_order' => 1, 'instructions' => 'Send a SWIFT transfer to the account below and upload the receipt.'],
            ['name' => 'USDT (TRC20)',   'type' => 'crypto', 'currency' => 'USDT', 'sort_order' => 2, 'instructions' => 'Send USDT on the TRON (TRC20) network to the wallet below.'],
            ['name' => 'USDT (ERC20)',   'type' => 'crypto', 'currency' => 'USDT', 'sort_order' => 3, 'instructions' => 'Send USDT on the Ethereum (ERC20) network to the wallet below.'],
        ];
        foreach ($methods as $m) {
            PaymentMethod::updateOrCreate(['name' => $m['name']], $m);
        }

        // The central pool account (data synced from the trading server via API)
        PoolAccount::updateOrCreate(
            ['account_ref' => '800120'],
            ['name' => 'GrowthCapital Pool', 'capacity' => 10000, 'balance' => 10000, 'equity' => 10000, 'currency' => 'USD', 'is_active' => true]
        );
    }
}
