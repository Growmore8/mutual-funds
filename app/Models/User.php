<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laragear\WebAuthn\Contracts\WebAuthnAuthenticatable;
use Laragear\WebAuthn\WebAuthnAuthentication;

class User extends Authenticatable implements WebAuthnAuthenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, WebAuthnAuthentication;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'country',
        'address',
        'account_type_id',
        'pool_account_id',
        'plan_locked',
        'status',
        'kyc_status',
        'otp_verified_at',
        'referral_code',
        'referred_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->referral_code)) {
                do {
                    $code = 'GC' . strtoupper(\Illuminate\Support\Str::random(6));
                } while (static::where('referral_code', $code)->exists());
                $user->referral_code = $code;
            }
        });
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function referrals()
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    /** Shareable referral link. */
    public function referralLink(): string
    {
        return url('/register?ref=' . $this->referral_code);
    }

    /** Total referral commission earned (1% of referred clients' deposits). */
    public function referralEarned(): float
    {
        return (float) $this->transactions()->where('type', 'referral')->sum('amount');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function hasPin(): bool
    {
        return ! empty($this->pin_hash);
    }

    /** Display client ID, e.g. GC000042. */
    public function clientCode(): string
    {
        return 'GC' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function accountType()
    {
        return $this->belongsTo(AccountType::class);
    }

    /** Assigned live/pool account ("live ID"). */
    public function poolAccount()
    {
        return $this->belongsTo(PoolAccount::class);
    }

    public function accountRequests()
    {
        return $this->hasMany(AccountRequest::class);
    }

    /** Current account balance = deposits + profit − withdrawals (latest ledger balance). */
    public function currentBalance(): float
    {
        return (float) ($this->transactions()->latest('id')->value('balance_after') ?? 0);
    }

    /** Capital = Principal = Balance = total approved deposits. */
    public function totalDeposited(): float
    {
        return (float) $this->deposits()->where('status', 'approved')->sum('amount');
    }

    /** Running PnL = profit/loss + referral commission − profit already paid out (can be negative). */
    public function runningPnl(): float
    {
        $paidOut = (float) $this->withdrawals()->where('status', 'approved')->sum('amount');

        return round($this->totalProfit() + $this->referralEarned() - $paidOut, 2);
    }

    /** Re-evaluate the plan from the current total deposit, and align the
     *  Live ID (pool) to the plan's pool — moving the client's capital there. */
    public function recalcPlan(): void
    {
        if ($this->plan_locked) {
            return;   // admin set the plan/pool manually
        }

        $total = $this->totalDeposited();
        $plan = AccountType::forAmount($total);
        if (! $plan) {
            return;
        }

        $planChanged = (int) $this->account_type_id !== (int) $plan->id;
        $updates = [];

        if ($planChanged) {
            $updates['account_type_id'] = $plan->id;
        }

        // Auto-assign the plan's pool (Live ID) and move the client's capital there.
        if ($plan->pool_account_id && (int) $this->pool_account_id !== (int) $plan->pool_account_id) {
            $updates['pool_account_id'] = $plan->pool_account_id;
        }

        if (! $updates) {
            return;
        }

        $this->update($updates);

        if (isset($updates['pool_account_id'])) {
            // Keep all the client's deposits in their current plan's pool (value_date preserved).
            $this->deposits()->update(['pool_account_id' => $plan->pool_account_id]);
        }

        if ($planChanged) {
            AppNotification::notify(
                $this->id, 'info', 'Plan updated to ' . $plan->name,
                'Your plan is now ' . $plan->name . ' based on a total deposit of $' . number_format($total, 2) . '.',
                route('accounts.index'),
            );
        }
    }

    public function deposits()
    {
        return $this->hasMany(Deposit::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function kycDocuments()
    {
        return $this->hasMany(KycDocument::class);
    }

    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function withdrawalMethods()
    {
        return $this->hasMany(WithdrawalMethod::class)->latest('id');
    }

    public function appNotifications()
    {
        return $this->hasMany(AppNotification::class)->latest();
    }

    /** Total profit credited to this client. */
    public function totalProfit(): float
    {
        return (float) $this->transactions()->where('type', 'profit')->sum('amount');
    }

    /**
     * Profit-only withdrawable balance: total profit earned minus any
     * withdrawals already requested/approved (capital stays locked in the pool).
     */
    public function availableToWithdraw(): float
    {
        $locked = (float) $this->withdrawals()->whereIn('status', ['pending', 'approved'])->sum('amount');

        return round(max(0, $this->totalProfit() + $this->referralEarned() - $locked), 2);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'pin_hash',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'otp_verified_at' => 'datetime',
            'password' => 'hashed',
            'plan_locked' => 'boolean',
        ];
    }
}
