<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        // [name, side, price, available, min, max, pay_methods, completion, orders]
        $rows = [
            // BUY side (client buys USDT)
            ['CoinBazaar', 'buy', 97.10, 18000, 500, 300000, 'UPI, Bank Transfer', 99.10, 2640],
            ['RupeeX', 'buy', 97.35, 9500, 1000, 180000, 'UPI, IMPS', 98.40, 1180],
            ['MetaPay', 'buy', 97.55, 22000, 2000, 400000, 'Bank Transfer, RTGS', 99.60, 3720],
            ['BharatCrypto', 'buy', 97.05, 7000, 500, 120000, 'UPI', 97.90, 720],
            ['ZenTrade', 'buy', 97.62, 14000, 1000, 250000, 'UPI, Bank Transfer, IMPS', 99.20, 2050],
            ['NovaP2P', 'buy', 97.90, 40000, 5000, 800000, 'Bank Transfer, RTGS', 99.90, 6310],
            ['PaySwift', 'buy', 97.25, 11000, 500, 200000, 'UPI, IMPS', 98.70, 1430],
            // SELL side (client sells USDT)
            ['CashOutPro', 'sell', 96.30, 16000, 500, 300000, 'UPI, Bank Transfer', 99.00, 1920],
            ['InstaSell', 'sell', 96.05, 8500, 1000, 150000, 'UPI', 97.80, 880],
            ['VaultExchange', 'sell', 95.80, 26000, 2000, 500000, 'Bank Transfer, IMPS, RTGS', 99.70, 4480],
            ['RapidFiat', 'sell', 96.20, 12000, 500, 220000, 'UPI, IMPS', 98.50, 1610],
            ['GoldenP2P', 'sell', 95.95, 33000, 5000, 700000, 'Bank Transfer, RTGS', 99.85, 5920],
            ['EasyRupee', 'sell', 96.45, 9000, 500, 160000, 'UPI', 98.10, 990],
            ['TrustCash', 'sell', 96.00, 20000, 1000, 350000, 'UPI, Bank Transfer', 99.30, 3140],
        ];
        $sort = 100;
        foreach ($rows as $r) {
            if (DB::table('p2p_merchants')->where('name', $r[0])->exists()) {
                continue;
            }
            DB::table('p2p_merchants')->insert([
                'name' => $r[0], 'side' => $r[1], 'asset' => 'USDT', 'currency' => 'INR',
                'price' => $r[2], 'available' => $r[3], 'min_limit' => $r[4], 'max_limit' => $r[5],
                'pay_methods' => $r[6], 'completion' => $r[7], 'orders_30d' => $r[8],
                'is_active' => true, 'sort' => $sort++, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void {}
};
