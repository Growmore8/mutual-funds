<?php

use App\Models\SpotInstrument;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Add the full CubeX-priced catalog: 88 US (NYSE/NASDAQ) stocks + 98 crypto.
     * Existing symbols are just (re)enabled; missing ones are created. NSE kept as-is.
     */
    public function up(): void
    {
        // [symbol, name, exchange]
        $us = [
            ['AAPL', 'Apple', 'NASDAQ'], ['ABBV', 'AbbVie', 'NYSE'], ['ABNB', 'Airbnb', 'NASDAQ'], ['ADBE', 'Adobe', 'NASDAQ'],
            ['AMAT', 'Applied Materials', 'NASDAQ'], ['AMD', 'AMD', 'NASDAQ'], ['AMGN', 'Amgen', 'NASDAQ'], ['AMZN', 'Amazon', 'NASDAQ'],
            ['ASML', 'ASML', 'NASDAQ'], ['AVGO', 'Broadcom', 'NASDAQ'], ['AXP', 'American Express', 'NYSE'], ['BA', 'Boeing', 'NYSE'],
            ['BABA', 'Alibaba', 'NYSE'], ['BAC', 'Bank of America', 'NYSE'], ['BIDU', 'Baidu', 'NASDAQ'], ['BLK', 'BlackRock', 'NYSE'],
            ['BMY', 'Bristol Myers Squibb', 'NYSE'], ['C', 'Citigroup', 'NYSE'], ['CAT', 'Caterpillar', 'NYSE'], ['CMCSA', 'Comcast', 'NASDAQ'],
            ['COIN', 'Coinbase', 'NASDAQ'], ['COP', 'ConocoPhillips', 'NYSE'], ['COST', 'Costco', 'NASDAQ'], ['CRM', 'Salesforce', 'NYSE'],
            ['CSCO', 'Cisco', 'NASDAQ'], ['CVS', 'CVS Health', 'NYSE'], ['CVX', 'Chevron', 'NYSE'], ['DE', 'Deere', 'NYSE'],
            ['DIS', 'Walt Disney', 'NYSE'], ['GE', 'GE Aerospace', 'NYSE'], ['GLD', 'SPDR Gold ETF', 'NYSE'], ['GOOG', 'Alphabet C', 'NASDAQ'],
            ['GOOGL', 'Alphabet A', 'NASDAQ'], ['GS', 'Goldman Sachs', 'NYSE'], ['HD', 'Home Depot', 'NYSE'], ['HON', 'Honeywell', 'NASDAQ'],
            ['INTC', 'Intel', 'NASDAQ'], ['IWM', 'iShares Russell 2000 ETF', 'NYSE'], ['JD', 'JD.com', 'NASDAQ'], ['JNJ', 'Johnson & Johnson', 'NYSE'],
            ['JPM', 'JPMorgan Chase', 'NYSE'], ['KO', 'Coca-Cola', 'NYSE'], ['LLY', 'Eli Lilly', 'NYSE'], ['LMT', 'Lockheed Martin', 'NYSE'],
            ['MA', 'Mastercard', 'NYSE'], ['MCD', "McDonald's", 'NYSE'], ['META', 'Meta Platforms', 'NASDAQ'], ['MMM', '3M', 'NYSE'],
            ['MRK', 'Merck', 'NYSE'], ['MS', 'Morgan Stanley', 'NYSE'], ['MSFT', 'Microsoft', 'NASDAQ'], ['MU', 'Micron', 'NASDAQ'],
            ['NFLX', 'Netflix', 'NASDAQ'], ['NIO', 'NIO', 'NYSE'], ['NKE', 'Nike', 'NYSE'], ['NOW', 'ServiceNow', 'NYSE'],
            ['NVDA', 'NVIDIA', 'NASDAQ'], ['ORCL', 'Oracle', 'NYSE'], ['PEP', 'PepsiCo', 'NASDAQ'], ['PFE', 'Pfizer', 'NYSE'],
            ['PG', 'Procter & Gamble', 'NYSE'], ['PLTR', 'Palantir', 'NASDAQ'], ['PYPL', 'PayPal', 'NASDAQ'], ['QCOM', 'Qualcomm', 'NASDAQ'],
            ['QQQ', 'Invesco QQQ ETF', 'NASDAQ'], ['RTX', 'RTX', 'NYSE'], ['SBUX', 'Starbucks', 'NASDAQ'], ['SCHW', 'Charles Schwab', 'NYSE'],
            ['SLB', 'Schlumberger', 'NYSE'], ['SLV', 'iShares Silver ETF', 'NYSE'], ['SNOW', 'Snowflake', 'NYSE'], ['SONY', 'Sony', 'NYSE'],
            ['SPY', 'SPDR S&P 500 ETF', 'NYSE'], ['T', 'AT&T', 'NYSE'], ['TGT', 'Target', 'NYSE'], ['TM', 'Toyota', 'NYSE'],
            ['TMUS', 'T-Mobile', 'NASDAQ'], ['TRP', 'TC Energy', 'NYSE'], ['TSLA', 'Tesla', 'NASDAQ'], ['TSM', 'TSMC', 'NYSE'],
            ['TXN', 'Texas Instruments', 'NASDAQ'], ['UBER', 'Uber', 'NYSE'], ['UNH', 'UnitedHealth', 'NYSE'], ['V', 'Visa', 'NYSE'],
            ['VZ', 'Verizon', 'NYSE'], ['WFC', 'Wells Fargo', 'NYSE'], ['WMT', 'Walmart', 'NYSE'], ['XOM', 'Exxon Mobil', 'NYSE'],
        ];

        $crypto = [
            '1INCH/USD', 'AAVE/USD', 'ADA/USD', 'ADA/USDT', 'ALGO/USD', 'ANKR/USD', 'APE/USD', 'APT/USD', 'ARB/USD', 'ATOM/USD',
            'ATOM/USDT', 'AVAX/USD', 'AVAX/USDT', 'AXS/USD', 'BAT/USD', 'BNB/USD', 'BNB/USDT', 'BNX/USD', 'BONK/USD', 'BTC/USD',
            'BTC/USDT', 'CELR/USD', 'COMP/USD', 'CRO/USD', 'CRV/USD', 'DASH/USD', 'DOGE/USD', 'DOGE/USDT', 'DOT/USD', 'DOT/USDT',
            'DYM/USD', 'EGLD/USD', 'ENA/USD', 'ENJ/USD', 'EOS/USD', 'ETH/USD', 'ETH/USDT', 'FIL/USD', 'FLOW/USD', 'FTM/USD',
            'GALA/USD', 'GMT/USD', 'GRT/USD', 'HBAR/USD', 'ICP/USD', 'INJ/USD', 'IOTA/USD', 'JTO/USD', 'JUP/USD', 'KLAY/USD',
            'KSM/USD', 'LINK/USD', 'LINK/USDT', 'LTC/USD', 'LTC/USDT', 'MANA/USD', 'MATIC/USD', 'MATIC/USDT', 'MEW/USD', 'MKR/USD',
            'NEAR/USD', 'NEO/USD', 'ONE/USD', 'OP/USD', 'PENDLE/USD', 'PEPE/USD', 'PYTH/USD', 'ROSE/USD', 'RUNE/USD', 'SAND/USD',
            'SEI/USD', 'SHIB/USD', 'SHIB/USDT', 'SNX/USD', 'SOL/USD', 'SOL/USDT', 'STRK/USD', 'STX/USD', 'SUI/USD', 'SUSHI/USD',
            'THETA/USD', 'TIA/USD', 'TRX/USD', 'UNI/USD', 'UNI/USDT', 'VET/USD', 'WAVES/USD', 'WIF/USD', 'WLD/USD', 'XLM/USD',
            'XLM/USDT', 'XMR/USD', 'XRP/USD', 'XRP/USDT', 'YFI/USD', 'ZEC/USD', 'ZIL/USD', 'ZK/USD',
        ];

        $sort = (int) SpotInstrument::max('sort');

        foreach ($us as [$symbol, $name, $exchange]) {
            if ($ex = SpotInstrument::where('symbol', $symbol)->first()) {
                $ex->update(['enabled' => true]);

                continue;
            }
            SpotInstrument::create(['symbol' => $symbol, 'name' => $name, 'exchange' => $exchange,
                'market' => 'global', 'type' => 'stock', 'currency' => 'USD', 'enabled' => true, 'sort' => ++$sort]);
        }

        foreach ($crypto as $symbol) {
            if ($ex = SpotInstrument::where('symbol', $symbol)->first()) {
                $ex->update(['enabled' => true]);

                continue;
            }
            SpotInstrument::create(['symbol' => $symbol, 'name' => explode('/', $symbol)[0], 'exchange' => null,
                'market' => 'crypto', 'type' => 'crypto', 'currency' => 'USD', 'enabled' => true, 'sort' => ++$sort]);
        }
    }

    public function down(): void
    {
        // keep the instruments (no-op)
    }
};
