<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PnlAllocation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'allocation_date' => 'date',
        'eligible_capital' => 'decimal:2',
        'weight' => 'decimal:8',
        'gross_pnl' => 'decimal:2',
        'fee' => 'decimal:2',
        'net_pnl' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function poolSnapshot()
    {
        return $this->belongsTo(PoolSnapshot::class);
    }
}
