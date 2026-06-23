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
}
