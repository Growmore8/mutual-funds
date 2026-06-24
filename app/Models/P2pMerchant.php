<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class P2pMerchant extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function curSym(): string
    {
        return $this->currency === 'INR' ? '₹' : '$';
    }
}
