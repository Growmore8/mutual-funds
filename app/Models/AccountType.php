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
}
