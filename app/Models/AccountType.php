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

        // Exact range match first (respects each plan's min/max cap).
        foreach ($types as $t) {
            if ($amount >= (float) $t->min_deposit && ($t->max_deposit === null || $amount <= (float) $t->max_deposit)) {
                return $t;
            }
        }

        // No exact match (a gap between plans, or above the top plan):
        // take the highest plan whose minimum the amount still meets.
        $eligible = $types->filter(fn ($t) => $amount >= (float) $t->min_deposit);

        // Below the cheapest plan -> the cheapest plan.
        return $eligible->isNotEmpty() ? $eligible->last() : $types->first();
    }
}
