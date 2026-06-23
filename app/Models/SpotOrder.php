<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpotOrder extends Model
{
    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:6', 'qty' => 'decimal:6', 'filled_qty' => 'decimal:6', 'is_maker' => 'boolean',
    ];

    public function instrument()
    {
        return $this->belongsTo(SpotInstrument::class, 'instrument_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function remaining(): float
    {
        return (float) $this->qty - (float) $this->filled_qty;
    }
}
