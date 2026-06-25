<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpotAccount extends Model
{
    protected $guarded = [];

    protected $casts = ['balance' => 'decimal:2'];

    protected static function booted(): void
    {
        // New spot wallets get a distinct "ST" account number (existing ones keep theirs / fall back).
        static::creating(function (SpotAccount $acc) {
            if (empty($acc->account_no)) {
                $acc->account_no = self::nextAccountNo();
            }
        });
    }

    /** Next spot account number, e.g. ST40001. */
    public static function nextAccountNo(): string
    {
        $last = static::where('account_no', 'like', 'ST%')
            ->orderByRaw('CAST(SUBSTRING(account_no, 3) AS UNSIGNED) DESC')
            ->value('account_no');

        $n = $last ? ((int) substr($last, 2)) + 1 : 40001;

        return 'ST' . str_pad((string) $n, 5, '0', STR_PAD_LEFT);
    }

    /** Display spot account number, e.g. ST40001. */
    public function code(): string
    {
        return $this->account_no ?: ('ST' . str_pad((string) (40000 + $this->id), 5, '0', STR_PAD_LEFT));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
