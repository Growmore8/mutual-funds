<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class P2pOrder extends Model
{
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function merchant()
    {
        return $this->belongsTo(P2pMerchant::class, 'p2p_merchant_id');
    }
}
