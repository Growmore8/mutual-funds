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

        // 4 mutual-fund account types (manageable from the admin dashboard)
        $types = [
            ['name' => 'Starter',  'slug' => 'starter',  'min_deposit' => 250,    'max_deposit' => 2499,   'profit_share_pct' => 70, 'management_fee_pct' => 5, 'lock_in_months' => 1,  'sort_order' => 1, 'description' => 'Entry-level managed fund for first-time investors.', 'features' => ['Pooled trading', 'Daily profit share', '24/7 support']],
            ['name' => 'Growth',   'slug' => 'growth',   'min_deposit' => 2500,   'max_deposit' => 9999,   'profit_share_pct' => 75, 'management_fee_pct' => 5, 'lock_in_months' => 2,  'sort_order' => 2, 'description' => 'Balanced growth with a higher profit share.', 'features' => ['Higher profit share', 'Priority support', 'Monthly reports']],
            ['name' => 'Premium',  'slug' => 'premium',  'min_deposit' => 10000,  'max_deposit' => 24999,  'profit_share_pct' => 80, 'management_fee_pct' => 5, 'lock_in_months' => 3,  'sort_order' => 3, 'description' => 'Premium tier for serious investors.', 'features' => ['Top profit share', 'Dedicated manager', 'Faster withdrawals']],
            ['name' => 'Elite',    'slug' => 'elite',    'min_deposit' => 25000,  'max_deposit' => null,   'profit_share_pct' => 85, 'management_fee_pct' => 5, 'lock_in_months' => 5,  'sort_order' => 4, 'description' => 'Flagship plan with the highest profit share.', 'features' => ['Maximum profit share', 'VIP support', 'Compounding option']],
        ];
        foreach ($types as $t) {
            AccountType::updateOrCreate(['slug' => $t['slug']], $t);
        }

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
