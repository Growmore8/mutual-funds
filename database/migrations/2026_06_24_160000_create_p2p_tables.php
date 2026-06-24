<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('p2p_merchants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('side')->default('buy');     // 'buy' = shown in client Buy tab (merchant sells), 'sell' = client Sell tab
            $table->string('asset')->default('USDT');    // what's traded
            $table->string('currency')->default('INR');  // fiat
            $table->decimal('price', 15, 4)->default(0); // price per 1 asset in fiat
            $table->decimal('available', 18, 4)->default(0);
            $table->decimal('min_limit', 15, 2)->default(0);
            $table->decimal('max_limit', 15, 2)->default(0);
            $table->string('pay_methods')->nullable();   // "UPI, Bank Transfer, IMPS"
            $table->decimal('completion', 5, 2)->default(98);
            $table->integer('orders_30d')->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('p2p_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('p2p_merchant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('side');                      // client's action: buy | sell
            $table->string('asset')->default('USDT');
            $table->string('currency')->default('INR');
            $table->decimal('price', 15, 4)->default(0);
            $table->decimal('fiat_amount', 15, 2)->default(0);   // amount in fiat
            $table->decimal('asset_amount', 18, 4)->default(0);  // amount of asset
            $table->string('status')->default('pending');        // pending | completed | cancelled
            $table->string('note')->nullable();
            $table->timestamps();
        });

        // Seed Binance-style dummy merchants.
        $now = now();
        $rows = [
            ['CryptoKing', 'buy', 97.20, 12500, 500, 200000, 'UPI, Bank Transfer, IMPS', 99.30, 1820],
            ['FastPay Traders', 'buy', 97.45, 8000, 1000, 150000, 'UPI, IMPS', 98.10, 940],
            ['TrustMerchant', 'buy', 97.80, 25000, 2000, 500000, 'Bank Transfer, RTGS', 99.80, 4210],
            ['SafeExchange', 'sell', 96.40, 15000, 500, 250000, 'UPI, Bank Transfer', 98.90, 1360],
            ['QuickCoin', 'sell', 96.10, 6000, 1000, 100000, 'UPI', 97.60, 610],
            ['PrimeP2P', 'sell', 95.90, 30000, 5000, 600000, 'Bank Transfer, IMPS, RTGS', 99.50, 5180],
        ];
        foreach ($rows as $i => $r) {
            DB::table('p2p_merchants')->insert([
                'name' => $r[0], 'side' => $r[1], 'asset' => 'USDT', 'currency' => 'INR',
                'price' => $r[2], 'available' => $r[3], 'min_limit' => $r[4], 'max_limit' => $r[5],
                'pay_methods' => $r[6], 'completion' => $r[7], 'orders_30d' => $r[8],
                'is_active' => true, 'sort' => $i, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('p2p_orders');
        Schema::dropIfExists('p2p_merchants');
    }
};
