<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Lunar\Base\LunarUser as LunarUserInterface;
use Lunar\Base\Traits\LunarUser;
use Lunar\Models\Customer;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasAvatar, LunarUserInterface
{
    use HasApiTokens, HasFactory, HasRoles, LunarUser, Notifiable, SoftDeletes;

    protected $fillable = [
        'username',
        'is_admin',
        'is_guest',
        'name',
        'email',
        'password',
        'phone',
        'phone_country_code',
        'status',
        'phone_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'is_admin' => 'boolean',
            'is_guest' => 'boolean',
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(EmergencyContact::class);
    }

    public function healthReports(): HasMany
    {
        return $this->hasMany(HealthReport::class);
    }

    public function userDevices(): MorphMany
    {
        return $this->morphMany(UserDevice::class, 'deviceable');
    }

    public function latestLoginActivity(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(LoginActivity::class)
            ->where('event', 'login')
            ->where('succeeded', true)
            ->latestOfMany('occurred_at');
    }

    public function guest(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Guest::class);
    }

    public function isGuest(): bool
    {
        return (bool) $this->is_guest;
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

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

    public function activeSubscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            });
    }

    public function machines(): HasMany
    {
        return $this->hasMany(Machine::class);
    }

    public function getMaxDevices(): int
    {
        return $this->activeSubscriptions()->count();
    }

    public function getMaxMachines(): int
    {
        return $this->activeSubscriptions()->count();
    }

    public function setting(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(UserSetting::class);
    }

    public function getOrCreateSetting(): UserSetting
    {
        return $this->setting ?? $this->setting()->create([
            'allow_app_notification' => true,
        ]);
    }

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
            $user->unlinkMachines();
            $user->recordDeletionActivity();

            $user->updateQuietly([
                'email' => $user->email.'_deleted_'.$user->id,
                'username' => $user->username.'_deleted_'.$user->id,
                'phone' => $user->phone.'_deleted_'.$user->id,
            ]);
        });

        static::restoring(function (User $user) {
            $user->email = preg_replace('/_deleted_\d+$/', '', $user->email);
            $user->username = preg_replace('/_deleted_\d+$/', '', $user->username);
            $user->phone = preg_replace('/_deleted_\d+$/', '', $user->phone);
        });
    }

    public function unlinkMachines(): void
    {
        $deviceIds = $this->machines()
            ->whereNotNull('device_id')
            ->pluck('device_id')
            ->filter()
            ->unique();

        if ($deviceIds->isNotEmpty()) {
            Device::whereIn('id', $deviceIds)->update([
                'user_id' => null,
            ]);
        }

        $this->machines()->update([
            'user_id' => null,
            'device_id' => null,
            'user_subscription_id' => null,
        ]);
    }

    public function latestDeletionActivity(): ?Activity
    {
        return Activity::query()
            ->where('subject_type', self::class)
            ->where('subject_id', $this->getKey())
            ->where('event', 'user-deleted')
            ->with('causer')
            ->latest()
            ->first();
    }

    public function deletionAudit(): ?array
    {
        $activity = $this->latestDeletionActivity();

        if (! $activity) {
            return null;
        }

        $properties = $activity->properties ?? collect();
        $causer = $activity->causer;

        return [
            'type' => $properties->get('deletion_type', 'system'),
            'deleted_at' => $activity->created_at?->toISOString(),
            'actor' => $causer ? [
                'id' => $causer->getKey(),
                'name' => $causer->name,
                'email' => $causer->email,
                'is_admin' => (bool) $causer->is_admin,
            ] : null,
        ];
    }

    protected function recordDeletionActivity(): void
    {
        $actor = auth()->user();

        activity()
            ->useLog('users')
            ->performedOn($this)
            ->when($actor, fn ($logger) => $logger->causedBy($actor))
            ->event('user-deleted')
            ->withProperties([
                'deletion_type' => $this->deletionType($actor),
                'deleted_user_id' => $this->getKey(),
                'deleted_user_email' => $this->email,
                'actor_user_id' => $actor?->getKey(),
                'actor_is_admin' => (bool) ($actor?->is_admin ?? false),
            ])
            ->log('user-deleted');
    }

    protected function deletionType(?User $actor): string
    {
        if (! $actor) {
            return 'system';
        }

        if ((int) $actor->getKey() === (int) $this->getKey()) {
            return 'self';
        }

        if ($actor->is_admin) {
            return 'admin';
        }

        return 'user';
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }

    public function getFilamentAvatarUrl(): ?string
    {

        return asset('logo.png');
    }
}
