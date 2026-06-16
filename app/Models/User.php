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
        'status',
        'kyc_status',
        'otp_verified_at',
    ];

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

    /** Total approved capital deposited. */
    public function totalDeposited(): float
    {
        return (float) $this->deposits()->where('status', 'approved')->sum('amount');
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

        return round(max(0, $this->totalProfit() - $locked), 2);
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
        ];
    }
}
