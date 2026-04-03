<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Lunar\Base\LunarUser as LunarUserInterface;
use Lunar\Base\Traits\LunarUser;
use Lunar\Models\Customer;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasAvatar, LunarUserInterface
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, LunarUser, Notifiable, SoftDeletes;

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
        'phone_country_code',
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
     * Get the user's active subscription (latest one).
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
     * Get all user's active subscriptions.
     */
    public function activeSubscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            });
    }

    /**
     * Get the machines bound to this user.
     */
    public function machines(): HasMany
    {
        return $this->hasMany(Machine::class);
    }

    /**
     * Get the maximum number of devices this user can bind.
     * Each active subscription allows 1 device.
     */
    public function getMaxDevices(): int
    {
        return $this->activeSubscriptions()->count();
    }

    /**
     * Get the maximum number of machines this user can have based on subscriptions.
     * Each active subscription allows 1 machine.
     */
    public function getMaxMachines(): int
    {
        return $this->activeSubscriptions()->count();
    }

    /**
     * Get the user's settings.
     */
    public function setting(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(UserSetting::class);
    }

    /**
     * Get or create user settings.
     */
    public function getOrCreateSetting(): UserSetting
    {
        return $this->setting ?? $this->setting()->create([
            'allow_app_notification' => true,
        ]);
    }

    /**
     * Get the user's Lunar customer, creating one if it doesn't exist.
     */
    public function getOrCreateCustomer(): Customer
    {
        $customer = $this->customers()->first();

        if (! $customer) {
            $customer = Customer::create([
                'first_name' => Str::before($this->name, ' '),
                'last_name' => Str::after($this->name, ' '),
            ]);

            $customer->users()->attach($this->id);
        }

        return $customer;
    }

    protected static function booted(): void
    {
        static::softDeleted(function (User $user) {
            $user->updateQuietly([
                'email' => $user->email . '_deleted_' . $user->id,
                'username' => $user->username . '_deleted_' . $user->id,
                'phone' => $user->phone . '_deleted_' . $user->id,
            ]);
        });

        static::restoring(function (User $user) {
            $user->email = preg_replace('/_deleted_\d+$/', '', $user->email);
            $user->username = preg_replace('/_deleted_\d+$/', '', $user->username);
            $user->phone = preg_replace('/_deleted_\d+$/', '', $user->phone);
        });
    }

    /**
     * Determine if the user can access the Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }

    /**
     * Get the avatar URL for Filament.
     */
    public function getFilamentAvatarUrl(): ?string
    {
        // Return system logo as default avatar
        return asset('logo.png');
    }
}
