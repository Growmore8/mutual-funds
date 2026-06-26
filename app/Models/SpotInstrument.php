<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpotInstrument extends Model
{
    protected $guarded = [];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function scopeEnabled($q)
    {
        return $q->where('enabled', true);
    }

    /** Twelve Data lookup symbol (e.g. RELIANCE on NSE, XAU/USD, BTC/USD). */
    public function tdSymbol(): string
    {
        return $this->symbol;
    }

    public function currencySymbol(): string
    {
        // Single USD base: all spot prices are stored & displayed in USD.
        return '$';
    }

    /** Company website domains for logos (LogoKit brand API) — used where ticker lookups miss (esp. NSE). */
    private const DOMAINS = [
        'RELIANCE' => 'ril.com', 'TCS' => 'tcs.com', 'HDFCBANK' => 'hdfcbank.com', 'INFY' => 'infosys.com',
        'ICICIBANK' => 'icicibank.com', 'SBIN' => 'sbi.co.in', 'ITC' => 'itcportal.com', 'AXISBANK' => 'axisbank.com',
        'KOTAKBANK' => 'kotak.com', 'LT' => 'larsentoubro.com', 'BHARTIARTL' => 'airtel.in', 'HINDUNILVR' => 'hul.co.in',
        'BAJFINANCE' => 'bajajfinserv.in', 'MARUTI' => 'marutisuzuki.com', 'WIPRO' => 'wipro.com', 'TATAMOTORS' => 'tatamotors.com',
        'SUNPHARMA' => 'sunpharma.com', 'ADANIENT' => 'adanienterprises.com', 'HCLTECH' => 'hcltech.com', 'ASIANPAINT' => 'asianpaints.com',
        'BAJAJFINSV' => 'bajajfinserv.in', 'TITAN' => 'titancompany.in', 'ULTRACEMCO' => 'ultratechcement.com', 'NESTLEIND' => 'nestle.in',
        'POWERGRID' => 'powergrid.in', 'NTPC' => 'ntpc.co.in', 'ONGC' => 'ongcindia.com', 'COALINDIA' => 'coalindia.in',
        'TATASTEEL' => 'tatasteel.com', 'JSWSTEEL' => 'jsw.in', 'TECHM' => 'techmahindra.com', 'GRASIM' => 'grasim.com',
        'HDFCLIFE' => 'hdfclife.com', 'DRREDDY' => 'drreddys.com', 'CIPLA' => 'cipla.com', 'BRITANNIA' => 'britannia.co.in',
        'ADANIPORTS' => 'adaniports.com', 'INDUSINDBK' => 'indusind.com', 'APOLLOHOSP' => 'apollohospitals.com', 'HINDALCO' => 'hindalco.com',
        'BPCL' => 'bharatpetroleum.in', 'SBILIFE' => 'sbilife.co.in', 'DIVISLAB' => 'divislabs.com',
    ];

    /** Real logo URL via LogoKit (brand domain / ticker / crypto); stored Twelve Data logo or monogram as fallback. */
    public function logoUrl(): ?string
    {
        $key = config('services.logokit.key');

        if ($this->market === 'crypto' || $this->type === 'crypto') {
            $base = strtolower(explode('/', $this->symbol)[0]);

            return $key
                ? "https://img.logokit.com/crypto/{$base}?token={$key}"
                : "https://cdn.jsdelivr.net/gh/spothq/cryptocurrency-icons@latest/128/color/{$base}.png";
        }

        if ($key) {
            // Prefer the brand domain (reliable for NSE); fall back to ticker lookup.
            if ($domain = (self::DOMAINS[$this->symbol] ?? null)) {
                return "https://img.logokit.com/{$domain}?token={$key}";
            }

            return 'https://img.logokit.com/ticker/' . urlencode($this->symbol) . "?token={$key}";
        }

        return $this->logo_url ?: null;
    }

    /** Secondary logo source (tried if the primary 404s). */
    public function logoFallback(): ?string
    {
        if ($this->market === 'crypto' || $this->type === 'crypto') {
            $base = strtolower(explode('/', $this->symbol)[0]);

            return "https://cdn.jsdelivr.net/gh/spothq/cryptocurrency-icons@latest/128/color/{$base}.png";
        }

        $key = config('services.logokit.key');

        // If a brand-domain logo was tried first, fall back to the ticker lookup, then the stored logo.
        if ($key && isset(self::DOMAINS[$this->symbol])) {
            return 'https://img.logokit.com/ticker/' . urlencode($this->symbol) . "?token={$key}";
        }

        return $this->logo_url ?: null;
    }

    /** 1–2 letter monogram for the symbol badge. */
    public function monogram(): string
    {
        return strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $this->symbol), 0, 2));
    }

    /** Deterministic badge color from the symbol. */
    public function badgeColor(): string
    {
        $colors = ['#2563eb', '#059669', '#d97706', '#dc2626', '#7c3aed', '#0891b2', '#db2777', '#ea580c', '#65a30d', '#4f46e5'];

        return $colors[crc32($this->symbol) % count($colors)];
    }
}
