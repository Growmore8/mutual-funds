<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountType extends Model
{
    protected $guarded = [];

    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean',
        'min_deposit' => 'decimal:2',
        'max_deposit' => 'decimal:2',
        'management_fee_pct' => 'decimal:2',
        'profit_share_pct' => 'decimal:2',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function poolAccount()
    {
        return $this->belongsTo(PoolAccount::class);
    }

    /**
     * Pick the plan for a total invested amount.
     * Boundary goes to the lower tier (e.g. $250 -> Silver, $300 -> Gold).
     * Above all ranges -> highest tier; below all -> lowest tier.
     */
    public static function forAmount(float $amount): ?self
    {
        $types = static::where('is_active', true)->orderBy('min_deposit')->get();
        if ($types->isEmpty()) {
            return null;
        }

        foreach ($types as $t) {
            if ($amount >= (float) $t->min_deposit && ($t->max_deposit === null || $amount <= (float) $t->max_deposit)) {
                return $t;
            }
        }

        // Below the cheapest plan -> lowest; otherwise above all -> highest.
        return $amount < (float) $types->first()->min_deposit ? $types->first() : $types->last();
    }
}
