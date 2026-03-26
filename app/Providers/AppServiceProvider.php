<?php

namespace App\Providers;

use App\Filament\Pages\LunarDashboard;
use App\Filament\Widgets\DashboardDateFilterWidget;
use App\Http\Middleware\EnsureIsAdmin;
use App\Listeners\LogConnectionPruned;
use App\Listeners\LogFailedLogin;
use App\Listeners\LogLogout;
use App\Listeners\LogSuccessfulLogin;
use App\Lunar\Extensions\CustomerGroupEditExtension;
use App\Lunar\Extensions\CustomerGroupResourceExtension;
use App\Lunar\Extensions\ShippingMethodEditExtension;
use App\Lunar\Extensions\ShippingMethodListExtension;
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
use Lunar\Admin\Filament\Resources\CustomerGroupResource;
use Lunar\Admin\Filament\Resources\CustomerGroupResource\Pages\EditCustomerGroup;
use Lunar\Shipping\Filament\Resources\ShippingMethodResource\Pages\EditShippingMethod;
use Lunar\Shipping\Filament\Resources\ShippingMethodResource\Pages\ListShippingMethod;
use Lunar\Shipping\ShippingPlugin;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Replace Lunar's hardcoded Dashboard with our custom one that includes the date filter widget
        $pagesRef = new \ReflectionProperty(\Lunar\Admin\LunarPanelManager::class, 'pages');
        $pagesRef->setAccessible(true);
        $pagesRef->setValue(null, array_map(
            fn ($page) => $page === \Lunar\Admin\Filament\Pages\Dashboard::class
                ? LunarDashboard::class
                : $page,
            $pagesRef->getValue()
        ));

        LunarPanel::disableTwoFactorAuth()
            ->extensions([
                EditCustomerGroup::class => CustomerGroupEditExtension::class,
                CustomerGroupResource::class => CustomerGroupResourceExtension::class,
                EditShippingMethod::class => ShippingMethodEditExtension::class,
                ListShippingMethod::class => ShippingMethodListExtension::class,
            ])
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
                    ->brandLogo(asset('logo.png'))
                    ->darkModeBrandLogo(asset('logo.png'))
                    ->favicon(asset('favicon.ico'))
                    ->plugin(new ShippingPlugin)
                    ->widgets([
                        DashboardDateFilterWidget::class,
                    ]);
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

        \Filament\Support\Facades\FilamentView::registerRenderHook(
            \Filament\View\PanelsRenderHook::PAGE_START,
            fn () => new \Illuminate\Support\HtmlString(
                '<div class="px-6 pt-6">
                    <a href="/dashboard" class="inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm font-medium shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-800 dark:ring-white/20">
                        <img src="' . asset('logo.png') . '" alt="Lunar logo" class="rounded-full size-8" style="background: black;" />
                        <span class="text-gray-700 dark:text-gray-200">Back to Admin Panel</span>
                    </a>
                </div>'
            ),
            scopes: \App\Filament\Pages\LunarDashboard::class,
        );

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
