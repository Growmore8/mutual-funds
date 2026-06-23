<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpotTrade extends Model
{
    protected $guarded = [];

    protected $casts = ['price' => 'decimal:6', 'qty' => 'decimal:6'];

    public function instrument()
    {
        return $this->belongsTo(SpotInstrument::class, 'instrument_id');
    }
}
