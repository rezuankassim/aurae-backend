<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Lunar\Base\LunarUser as LunarUserInterface;
use Lunar\Base\Traits\LunarUser;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, LunarUserInterface
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, LunarUser, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'is_admin',
        'name',
        'email',
        'password',
        'phone',
        'status',
        'phone_verified_at',
    ];

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
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the addresses associated with the user.
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    /**
     * Get the health reportss associated with the user.
     */
    public function healthReports(): HasMany
    {
        return $this->hasMany(HealthReport::class);
    }

    /**
     * Get the devices associated with the user.
     */
    public function userDevices(): MorphMany
    {
        return $this->morphMany(UserDevice::class, 'deviceable');
    }

    /**
     * Get the guest record if this user is a guest.
     */
    public function guest(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Guest::class);
    }

    /**
     * Check if this user is a guest user.
     */
    public function isGuest(): bool
    {
        return $this->guest()->exists();
    }

    /**
     * Get the user subscriptions.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    /**
     * Get the user's active subscription.
     */
    public function activeSubscription(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(UserSubscription::class)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            })
            ->latest();
    }

    /**
     * Get the maximum number of devices this user can have based on subscription.
     */
    public function getMaxDevices(): int
    {
        $activeSubscription = $this->activeSubscription()->with('subscription')->first();

        return $activeSubscription?->subscription?->max_devices ?? 1;
    }

    /**
     * Determine if the user can access the Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }
}
