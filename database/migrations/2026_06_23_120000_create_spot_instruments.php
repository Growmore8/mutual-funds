<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spot_instruments', function (Blueprint $table) {
            $table->id();
            $table->string('symbol');                 // Twelve Data symbol, e.g. RELIANCE, XAU/USD, BTC/USD, AAPL
            $table->string('name')->nullable();
            $table->string('exchange')->nullable();   // NSE, BSE, NASDAQ, etc.
            $table->string('market')->default('global'); // india | global | crypto | forex | commodity
            $table->string('type')->default('stock');    // stock | crypto | forex | commodity | index
            $table->boolean('enabled')->default(true);
            $table->integer('sort')->default(0);
            $table->timestamps();
            $table->unique(['symbol', 'exchange']);
        });

        // India first, then global / crypto / forex / commodity.
        $seed = [
            ['RELIANCE', 'Reliance Industries', 'NSE', 'india', 'stock'],
            ['TCS', 'Tata Consultancy Services', 'NSE', 'india', 'stock'],
            ['HDFCBANK', 'HDFC Bank', 'NSE', 'india', 'stock'],
            ['INFY', 'Infosys', 'NSE', 'india', 'stock'],
            ['ICICIBANK', 'ICICI Bank', 'NSE', 'india', 'stock'],
            ['SBIN', 'State Bank of India', 'NSE', 'india', 'stock'],
            ['AAPL', 'Apple', 'NASDAQ', 'global', 'stock'],
            ['MSFT', 'Microsoft', 'NASDAQ', 'global', 'stock'],
            ['TSLA', 'Tesla', 'NASDAQ', 'global', 'stock'],
            ['AMZN', 'Amazon', 'NASDAQ', 'global', 'stock'],
            ['BTC/USD', 'Bitcoin', null, 'crypto', 'crypto'],
            ['ETH/USD', 'Ethereum', null, 'crypto', 'crypto'],
            ['EUR/USD', 'Euro / US Dollar', null, 'forex', 'forex'],
            ['GBP/USD', 'Pound / US Dollar', null, 'forex', 'forex'],
            ['XAU/USD', 'Gold', null, 'commodity', 'commodity'],
            ['XAG/USD', 'Silver', null, 'commodity', 'commodity'],
        ];

        foreach ($seed as $i => [$symbol, $name, $exchange, $market, $type]) {
            DB::table('spot_instruments')->insert([
                'symbol' => $symbol,
                'name' => $name,
                'exchange' => $exchange,
                'market' => $market,
                'type' => $type,
                'enabled' => true,
                'sort' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('spot_instruments');
    }
};
