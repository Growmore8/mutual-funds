<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoolAccount extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
        'capacity' => 'decimal:2',
        'balance' => 'decimal:2',
        'equity' => 'decimal:2',
    ];

    public function snapshots()
    {
        return $this->hasMany(PoolSnapshot::class);
    }
}
