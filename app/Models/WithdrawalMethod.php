<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithdrawalMethod extends Model
{
    protected $guarded = [];

    protected $casts = ['details' => 'array'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Short label for the picker, e.g. "USDT · TRC20" or "HDFC Bank". */
    public function title(): string
    {
        $d = $this->details ?? [];

        return match ($this->type) {
            'crypto' => trim(($d['currency'] ?? 'Crypto') . ' · ' . ($d['network'] ?? '')),
            'upi' => trim(($d['provider'] ?? 'UPI') . ' · UPI'),
            default => ($d['bank_name'] ?? 'Bank'),
        };
    }

    /** Full payout details snapshot stored on the withdrawal request. */
    public function summary(): string
    {
        $d = $this->details ?? [];

        return match ($this->type) {
            'crypto' => trim(($d['currency'] ?? '') . ' ' . ($d['network'] ?? '')) . ' wallet: ' . ($d['wallet'] ?? '—'),
            'upi' => 'UPI (' . ($d['provider'] ?? '') . '): ' . ($d['upi_id'] ?? '—'),
            default => 'Bank: ' . ($d['account_name'] ?? '') . ' · A/C ' . ($d['account_number'] ?? '—') . ' · ' . ($d['bank_name'] ?? '') . ' · IFSC ' . ($d['ifsc'] ?? '—'),
        };
    }
}
