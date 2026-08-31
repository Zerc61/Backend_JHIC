<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'role',
        'status',
        'loyalty_tier',
        'last_coin_activity_at',
        'referral_code',
        'referrer_user_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'last_coin_activity_at' => 'datetime',
        ];
    }

    // --- Relationships ---

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function umkm()
    {
        return $this->hasOne(Umkm::class);
    }

    public function managedDestinations()
    {
        return $this->hasMany(Destination::class, 'manager_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function topUpTransactions()
    {
        return $this->hasMany(TopUpTransaction::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function loyaltyRewards()
    {
        return $this->hasMany(LoyaltyReward::class);
    }

    public function voucherClaims()
    {
        return $this->hasMany(VoucherClaim::class);
    }

    public function tripPlans()
    {
        return $this->hasMany(TripPlan::class);
    }

    public function createdEvents()
    {
        return $this->hasMany(Event::class, 'created_by');
    }

    // --- Helper Methods ---

    public function isTourist(): bool
    {
        return $this->role === UserRole::TOURIST;
    }

    public function isUmkm(): bool
    {
        return $this->role === UserRole::UMKM;
    }

    public function isManager(): bool
    {
        return $this->role === UserRole::MANAGER;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function hasActiveWallet(): bool
    {
        return $this->wallet !== null;
    }
}