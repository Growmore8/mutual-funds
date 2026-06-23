<?php

use App\Models\SpotInstrument;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // [symbol, name, exchange, market, type]  — currency derived (india => INR else USD)
        $list = [
            // India (NSE) — INR
            ['ITC', 'ITC Ltd', 'NSE', 'india', 'stock'],
            ['AXISBANK', 'Axis Bank', 'NSE', 'india', 'stock'],
            ['KOTAKBANK', 'Kotak Mahindra Bank', 'NSE', 'india', 'stock'],
            ['LT', 'Larsen & Toubro', 'NSE', 'india', 'stock'],
            ['BHARTIARTL', 'Bharti Airtel', 'NSE', 'india', 'stock'],
            ['HINDUNILVR', 'Hindustan Unilever', 'NSE', 'india', 'stock'],
            ['BAJFINANCE', 'Bajaj Finance', 'NSE', 'india', 'stock'],
            ['MARUTI', 'Maruti Suzuki', 'NSE', 'india', 'stock'],
            ['WIPRO', 'Wipro', 'NSE', 'india', 'stock'],
            ['TATAMOTORS', 'Tata Motors', 'NSE', 'india', 'stock'],
            ['SUNPHARMA', 'Sun Pharma', 'NSE', 'india', 'stock'],
            ['ADANIENT', 'Adani Enterprises', 'NSE', 'india', 'stock'],
            // US — USD
            ['GOOGL', 'Alphabet', 'NASDAQ', 'global', 'stock'],
            ['META', 'Meta Platforms', 'NASDAQ', 'global', 'stock'],
            ['NVDA', 'NVIDIA', 'NASDAQ', 'global', 'stock'],
            ['NFLX', 'Netflix', 'NASDAQ', 'global', 'stock'],
            ['AMD', 'AMD', 'NASDAQ', 'global', 'stock'],
            ['INTC', 'Intel', 'NASDAQ', 'global', 'stock'],
            // Crypto
            ['BNB/USD', 'BNB', null, 'crypto', 'crypto'],
            ['XRP/USD', 'XRP', null, 'crypto', 'crypto'],
            ['SOL/USD', 'Solana', null, 'crypto', 'crypto'],
            ['DOGE/USD', 'Dogecoin', null, 'crypto', 'crypto'],
            ['ADA/USD', 'Cardano', null, 'crypto', 'crypto'],
            // Forex
            ['USD/JPY', 'US Dollar / Yen', null, 'forex', 'forex'],
            ['USD/INR', 'US Dollar / Rupee', null, 'forex', 'forex'],
            ['AUD/USD', 'Aussie / US Dollar', null, 'forex', 'forex'],
            // Commodity
            ['XPT/USD', 'Platinum', null, 'commodity', 'commodity'],
        ];

        $sort = (int) SpotInstrument::max('sort');
        foreach ($list as [$symbol, $name, $exchange, $market, $type]) {
            $sort++;
            SpotInstrument::updateOrCreate(
                ['symbol' => $symbol, 'exchange' => $exchange],
                ['name' => $name, 'market' => $market, 'type' => $type,
                    'currency' => $market === 'india' ? 'INR' : 'USD', 'enabled' => true, 'sort' => $sort],
            );
        }
    }

    public function down(): void
    {
        // keep the instruments (no-op)
    }
};
