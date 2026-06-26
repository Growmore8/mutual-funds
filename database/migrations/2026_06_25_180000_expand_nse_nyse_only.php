<?php

use App\Models\SpotInstrument;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Client wants Spot Trading to offer ONLY NSE (India) and NYSE/US stocks.
     * Add more large-caps in both markets and hide crypto/forex/commodity.
     */
    public function up(): void
    {
        // [symbol, name, exchange, market, type] — currency: india => INR, else USD
        $list = [
            // ---- NSE (India) large-caps ----
            ['HCLTECH', 'HCL Technologies', 'NSE', 'india', 'stock'],
            ['ASIANPAINT', 'Asian Paints', 'NSE', 'india', 'stock'],
            ['BAJAJFINSV', 'Bajaj Finserv', 'NSE', 'india', 'stock'],
            ['TITAN', 'Titan Company', 'NSE', 'india', 'stock'],
            ['ULTRACEMCO', 'UltraTech Cement', 'NSE', 'india', 'stock'],
            ['NESTLEIND', 'Nestle India', 'NSE', 'india', 'stock'],
            ['POWERGRID', 'Power Grid Corp', 'NSE', 'india', 'stock'],
            ['NTPC', 'NTPC Ltd', 'NSE', 'india', 'stock'],
            ['ONGC', 'Oil & Natural Gas Corp', 'NSE', 'india', 'stock'],
            ['COALINDIA', 'Coal India', 'NSE', 'india', 'stock'],
            ['TATASTEEL', 'Tata Steel', 'NSE', 'india', 'stock'],
            ['JSWSTEEL', 'JSW Steel', 'NSE', 'india', 'stock'],
            ['TECHM', 'Tech Mahindra', 'NSE', 'india', 'stock'],
            ['GRASIM', 'Grasim Industries', 'NSE', 'india', 'stock'],
            ['HDFCLIFE', 'HDFC Life Insurance', 'NSE', 'india', 'stock'],
            ['DRREDDY', "Dr. Reddy's Labs", 'NSE', 'india', 'stock'],
            ['CIPLA', 'Cipla', 'NSE', 'india', 'stock'],
            ['BRITANNIA', 'Britannia Industries', 'NSE', 'india', 'stock'],
            ['ADANIPORTS', 'Adani Ports & SEZ', 'NSE', 'india', 'stock'],
            ['INDUSINDBK', 'IndusInd Bank', 'NSE', 'india', 'stock'],
            ['APOLLOHOSP', 'Apollo Hospitals', 'NSE', 'india', 'stock'],
            ['HINDALCO', 'Hindalco Industries', 'NSE', 'india', 'stock'],
            ['BPCL', 'Bharat Petroleum', 'NSE', 'india', 'stock'],
            ['SBILIFE', 'SBI Life Insurance', 'NSE', 'india', 'stock'],
            ['DIVISLAB', "Divi's Laboratories", 'NSE', 'india', 'stock'],

            // ---- US (NYSE) large-caps ----
            ['JPM', 'JPMorgan Chase', 'NYSE', 'global', 'stock'],
            ['V', 'Visa', 'NYSE', 'global', 'stock'],
            ['MA', 'Mastercard', 'NYSE', 'global', 'stock'],
            ['WMT', 'Walmart', 'NYSE', 'global', 'stock'],
            ['DIS', 'Walt Disney', 'NYSE', 'global', 'stock'],
            ['KO', 'Coca-Cola', 'NYSE', 'global', 'stock'],
            ['BA', 'Boeing', 'NYSE', 'global', 'stock'],
            ['ORCL', 'Oracle', 'NYSE', 'global', 'stock'],
            ['CRM', 'Salesforce', 'NYSE', 'global', 'stock'],
            ['NKE', 'Nike', 'NYSE', 'global', 'stock'],
            ['MCD', "McDonald's", 'NYSE', 'global', 'stock'],
            ['XOM', 'Exxon Mobil', 'NYSE', 'global', 'stock'],
            ['CVX', 'Chevron', 'NYSE', 'global', 'stock'],
            ['PFE', 'Pfizer', 'NYSE', 'global', 'stock'],
            ['BAC', 'Bank of America', 'NYSE', 'global', 'stock'],
            ['JNJ', 'Johnson & Johnson', 'NYSE', 'global', 'stock'],
            ['PG', 'Procter & Gamble', 'NYSE', 'global', 'stock'],
            ['HD', 'Home Depot', 'NYSE', 'global', 'stock'],
            ['UNH', 'UnitedHealth Group', 'NYSE', 'global', 'stock'],

            // ---- US (NASDAQ) large-caps ----
            ['AVGO', 'Broadcom', 'NASDAQ', 'global', 'stock'],
            ['COST', 'Costco', 'NASDAQ', 'global', 'stock'],
            ['PEP', 'PepsiCo', 'NASDAQ', 'global', 'stock'],
            ['ADBE', 'Adobe', 'NASDAQ', 'global', 'stock'],
            ['CSCO', 'Cisco', 'NASDAQ', 'global', 'stock'],
            ['QCOM', 'Qualcomm', 'NASDAQ', 'global', 'stock'],
            ['TXN', 'Texas Instruments', 'NASDAQ', 'global', 'stock'],
            ['AMAT', 'Applied Materials', 'NASDAQ', 'global', 'stock'],
            ['PYPL', 'PayPal', 'NASDAQ', 'global', 'stock'],
            ['SBUX', 'Starbucks', 'NASDAQ', 'global', 'stock'],
            ['CMCSA', 'Comcast', 'NASDAQ', 'global', 'stock'],
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

        // Only NSE + NYSE/US are offered — hide everything else.
        DB::table('spot_instruments')->whereIn('market', ['crypto', 'forex', 'commodity'])->update(['enabled' => false]);
    }

    public function down(): void
    {
        // keep the instruments (no-op)
    }
};
