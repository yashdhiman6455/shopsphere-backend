<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'store_name',
        'seller_approved_at',
        'phone',
        'address',
        'city',
        'state',
        'zip_code',
        'image',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'seller_approved_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function cart(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function wishlist(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'wishlists')->withTimestamps();
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'seller_id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    public function isSeller(): bool
    {
        return $this->role === 'seller';
    }

    public function isApprovedSeller(): bool
    {
        return $this->isSeller() && ! is_null($this->seller_approved_at) && $this->is_active;
    }

    public function hasVerifiedEmail(): bool
    {
        return ! is_null($this->email_verified_at);
    }

    /**
     * Check whether the user has one of the given roles.
     */
    public function hasRole(string|array ...$roles): bool
    {
        if (isset($roles[0]) && is_array($roles[0])) {
            $roles = $roles[0];
        }

        return in_array($this->role, $roles, true);
    }

    /**
     * The list of permission names granted to the user's role.
     */
    public function permissions(): array
    {
        $granted = config("rbac.roles.{$this->role}", []);

        if (in_array('*', $granted, true)) {
            return array_keys(config('rbac.permissions', []));
        }

        return $granted;
    }

    /**
     * Check whether the user has been granted the given permission.
     */
    public function hasPermission(string $permission): bool
    {
        $granted = config("rbac.roles.{$this->role}", []);

        return in_array('*', $granted, true) || in_array($permission, $granted, true);
    }

    /**
     * Check whether the user has been granted any of the given permissions.
     */
    public function hasAnyPermission(string|array ...$permissions): bool
    {
        if (isset($permissions[0]) && is_array($permissions[0])) {
            $permissions = $permissions[0];
        }

        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }
}
