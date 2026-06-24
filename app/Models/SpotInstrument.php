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

    /** Logo URL for crypto (free CDN); stocks fall back to a monogram badge. */
    public function logoUrl(): ?string
    {
        if ($this->market === 'crypto' || $this->type === 'crypto') {
            $base = strtolower(explode('/', $this->symbol)[0]);

            return "https://assets.coincap.io/assets/icons/{$base}@2x.png";
        }

        return null;
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
