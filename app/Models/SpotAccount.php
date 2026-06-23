<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpotAccount extends Model
{
    protected $guarded = [];

    protected $casts = ['balance' => 'decimal:2'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
