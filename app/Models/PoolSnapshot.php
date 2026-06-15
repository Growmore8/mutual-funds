<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoolSnapshot extends Model
{
    protected $guarded = [];

    protected $casts = [
        'snapshot_date' => 'date',
        'distributed' => 'boolean',
        'raw' => 'array',
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'pnl' => 'decimal:2',
        'pnl_pct' => 'decimal:4',
    ];

    public function poolAccount()
    {
        return $this->belongsTo(PoolAccount::class);
    }

    public function allocations()
    {
        return $this->hasMany(PnlAllocation::class);
    }
}
