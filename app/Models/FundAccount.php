<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FundAccount extends Model
{
    protected $guarded = [];

    protected $casts = [
        'plan_locked' => 'boolean',
        'is_primary' => 'boolean',
        'locked' => 'boolean',
        'active' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Give every account its own unique account number (one login, many accounts).
        static::creating(function (FundAccount $acc) {
            if (empty($acc->account_no)) {
                $acc->account_no = self::nextAccountNo();
            }
        });
    }

    /** Next mutual-fund account number, e.g. MF600001. Existing GCA##### numbers are left as-is. */
    public static function nextAccountNo(): string
    {
        $last = static::where('account_no', 'like', 'MF%')
            ->orderByRaw('CAST(SUBSTRING(account_no, 3) AS UNSIGNED) DESC')
            ->value('account_no');

        $n = $last ? ((int) substr($last, 2)) + 1 : 600001;

        return 'MF' . str_pad((string) $n, 6, '0', STR_PAD_LEFT);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function accountType()
    {
        return $this->belongsTo(AccountType::class);
    }

    public function poolAccount()
    {
        return $this->belongsTo(PoolAccount::class);
    }

    public function deposits()
    {
        return $this->hasMany(Deposit::class);
    }

    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function pnlAllocations()
    {
        return $this->hasMany(PnlAllocation::class);
    }

    /** Each account's own unique number, e.g. GCA000007. */
    public function code(): string
    {
        return $this->account_no ?: ('GCA' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT));
    }

    /** Capital = approved deposits in this account. */
    public function totalDeposited(): float
    {
        return (float) $this->deposits()->where('status', 'approved')->sum('amount');
    }

    /** Profit credited to this account (incl. referral if booked here). */
    public function totalProfit(): float
    {
        return (float) $this->transactions()->whereIn('type', ['profit', 'referral'])->sum('amount');
    }

    /** Running PnL = profit − approved withdrawals (can be negative). */
    public function runningPnl(): float
    {
        $paidOut = (float) $this->withdrawals()->where('status', 'approved')->sum('amount');

        return round($this->totalProfit() - $paidOut, 2);
    }

    /** Profit-only withdrawable balance. */
    public function availableToWithdraw(): float
    {
        $locked = (float) $this->withdrawals()->whereIn('status', ['pending', 'approved'])->sum('amount');

        return round(max(0, $this->totalProfit() - $locked), 2);
    }

    /** Re-evaluate plan + pool from this account's total deposit (skips if locked). */
    public function recalcPlan(): void
    {
        if ($this->plan_locked) {
            return;
        }

        $plan = AccountType::forAmount($this->totalDeposited());
        if (! $plan) {
            return;
        }

        $updates = [];
        if ((int) $this->account_type_id !== (int) $plan->id) {
            $updates['account_type_id'] = $plan->id;
        }
        if ($plan->pool_account_id && (int) $this->pool_account_id !== (int) $plan->pool_account_id) {
            $updates['pool_account_id'] = $plan->pool_account_id;
        }

        if ($updates) {
            $this->update($updates);
            if (isset($updates['pool_account_id'])) {
                $this->deposits()->update(['pool_account_id' => $plan->pool_account_id]);
            }
        }
    }
}
