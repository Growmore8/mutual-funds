<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $guarded = [];

    protected $casts = [
        'details' => 'array',
        'is_active' => 'boolean',
    ];
}
