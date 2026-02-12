<?php

namespace App\Providers;

use App\Http\Middleware\EnsureIsAdmin;
use App\Listeners\LogConnectionPruned;
use App\Listeners\LogFailedLogin;
use App\Listeners\LogLogout;
use App\Listeners\LogSuccessfulLogin;
use App\PaymentTypes\SenangpayPayment;
use Filament\Http\Middleware\Authenticate;
use Filament\Panel;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Reverb\Events\ConnectionPruned;
use Lunar\Admin\Support\Facades\LunarPanel;
use Lunar\Shipping\ShippingPlugin;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        LunarPanel::disableTwoFactorAuth()
            ->panel(function (Panel $panel) {
                return $panel
                    ->authGuard('web')
                    ->authPasswordBroker('users')
                    ->authMiddleware([
                        Authenticate::class,
                        EnsureIsAdmin::class,
                    ])
                    ->userMenuItems([
                        'admin-panel' => \Filament\Navigation\MenuItem::make()
                            ->label('Back to Admin Panel')
                            ->url('/dashboard')
                            ->icon('heroicon-o-arrow-left-circle'),
                    ])
                    ->plugin(new ShippingPlugin);
            })->register();

        // Register SenangPay payment driver
        \Lunar\Facades\Payments::extend('senangpay', fn ($app) => $app->make(SenangpayPayment::class));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Lunar\Facades\Telemetry::optOut();

        Event::listen(Login::class, LogSuccessfulLogin::class);
        Event::listen(Logout::class, LogLogout::class);
        Event::listen(Failed::class, LogFailedLogin::class);
        Event::listen(ConnectionPruned::class, LogConnectionPruned::class);

        // Allow all admin users to access Lunar admin panel
        Gate::before(function ($user, $ability) {
            // Check if this is a Lunar admin panel permission
            if (str_starts_with($ability, 'lunar:') || str_starts_with($ability, 'catalog:') || str_starts_with($ability, 'sales:') || str_starts_with($ability, 'settings:')) {
                return $user && $user->is_admin ? true : null;
            }
        });
    }
}
