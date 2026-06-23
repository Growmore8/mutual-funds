<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpotHolding extends Model
{
    protected $guarded = [];

    protected $casts = ['qty' => 'decimal:6', 'avg_price' => 'decimal:6'];

    public function instrument()
    {
        return $this->belongsTo(SpotInstrument::class, 'instrument_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
