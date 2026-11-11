<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'google_id',
        'avatar',
        'phone',
        'address',
        'city',
        'password',
        'is_admin',
        'auth_provider',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Only staff accounts may open the admin panel. Every customer that
     * registers through the storefront lands in this same table, so without
     * this check they could all sign in to /admin.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->is_admin;
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function wishlistItems(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Link any guest orders placed with the same phone number or email to this customer account.
     */
    public function claimMatchingGuestOrders(): void
    {
        if (filled($this->phone)) {
            Order::query()
                ->whereNull('user_id')
                ->where('phone', $this->phone)
                ->update(['user_id' => $this->id]);
        }

        if (filled($this->email)) {
            Order::query()
                ->whereNull('user_id')
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($this->email)])
                ->update(['user_id' => $this->id]);
        }
    }
}
