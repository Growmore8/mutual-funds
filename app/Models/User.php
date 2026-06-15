<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
        'account_type_id',
        'status',
        'kyc_status',
        'otp_verified_at',
    ];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function accountType()
    {
        return $this->belongsTo(AccountType::class);
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

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
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
