<?php

namespace App\Providers;

use App\Filament\Pages\LunarDashboard;
use App\Filament\Widgets\DashboardDateFilterWidget;
use App\Http\Middleware\EnsureIsAdmin;
use App\Listeners\LogConnectionPruned;
use App\Listeners\LogFailedLogin;
use App\Listeners\LogLogout;
use App\Listeners\LogSuccessfulLogin;
use App\Lunar\Extensions\CollectionGroupCreateExtension;
use App\Lunar\Extensions\CollectionGroupEditExtension;
use App\Lunar\Extensions\CollectionGroupResourceExtension;
use App\Lunar\Extensions\CustomerGroupCreateExtension;
use App\Lunar\Extensions\CustomerGroupEditExtension;
use App\Lunar\Extensions\CustomerGroupResourceExtension;
use App\Lunar\Extensions\CustomerListExtension;
use App\Lunar\Extensions\DiscountEditExtension;
use App\Lunar\Extensions\DiscountListExtension;
use App\Lunar\Extensions\DiscountResourceExtension;
use App\Lunar\Extensions\ManageProductVariantsExtension;
use App\Lunar\Extensions\ProductConditionRelationManagerExtension;
use App\Lunar\Extensions\ProductLimitationRelationManagerExtension;
use App\Lunar\Extensions\ProductResourceExtension;
use App\Lunar\Extensions\ProductRewardRelationManagerExtension;
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
use Livewire\Livewire;
use Lunar\Admin\Filament\Resources\CollectionGroupResource;
use Lunar\Admin\Filament\Resources\CollectionGroupResource\Pages\CreateCollectionGroup;
use Lunar\Admin\Filament\Resources\CollectionGroupResource\Pages\EditCollectionGroup;
use Lunar\Admin\Filament\Resources\CustomerGroupResource;
use Lunar\Admin\Filament\Resources\CustomerGroupResource\Pages\CreateCustomerGroup;
use Lunar\Admin\Filament\Resources\CustomerGroupResource\Pages\EditCustomerGroup;
use Lunar\Admin\Filament\Resources\CustomerResource\Pages\ListCustomers;
use Lunar\Admin\Filament\Resources\DiscountResource;
use Lunar\Admin\Filament\Resources\DiscountResource\Pages\EditDiscount;
use Lunar\Admin\Filament\Resources\DiscountResource\Pages\ListDiscounts;
use Lunar\Admin\Filament\Resources\DiscountResource\RelationManagers\ProductConditionRelationManager;
use Lunar\Admin\Filament\Resources\DiscountResource\RelationManagers\ProductLimitationRelationManager;
use Lunar\Admin\Filament\Resources\DiscountResource\RelationManagers\ProductRewardRelationManager;
use Lunar\Admin\Filament\Resources\ProductResource;
use Lunar\Admin\Filament\Resources\ProductResource\Pages\ManageProductVariants;
use Lunar\Admin\Support\Facades\LunarPanel;
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

        // Remove AttributeGroupResource and ChannelResource from Lunar panel navigation
        $resourcesRef = new \ReflectionProperty(\Lunar\Admin\LunarPanelManager::class, 'resources');
        $resourcesRef->setAccessible(true);
        $resourcesRef->setValue(null, array_values(array_filter(
            $resourcesRef->getValue(),
            fn ($resource) => ! in_array($resource, [
                \Lunar\Admin\Filament\Resources\AttributeGroupResource::class,
                \Lunar\Admin\Filament\Resources\ChannelResource::class,
                \Lunar\Admin\Filament\Resources\ProductTypeResource::class,
            ])
        )));

        LunarPanel::disableTwoFactorAuth()
            ->extensions([
                CreateCollectionGroup::class => CollectionGroupCreateExtension::class,
                EditCollectionGroup::class => CollectionGroupEditExtension::class,
                CollectionGroupResource::class => CollectionGroupResourceExtension::class,
                ListCustomers::class => CustomerListExtension::class,
                CreateCustomerGroup::class => CustomerGroupCreateExtension::class,
                EditCustomerGroup::class => CustomerGroupEditExtension::class,
                CustomerGroupResource::class => CustomerGroupResourceExtension::class,
                EditDiscount::class => DiscountEditExtension::class,
                ListDiscounts::class => DiscountListExtension::class,
                DiscountResource::class => DiscountResourceExtension::class,
                ProductConditionRelationManager::class => ProductConditionRelationManagerExtension::class,
                ProductLimitationRelationManager::class => ProductLimitationRelationManagerExtension::class,
                ProductRewardRelationManager::class => ProductRewardRelationManagerExtension::class,
                ManageProductVariants::class => ManageProductVariantsExtension::class,
                ProductResource::class => ProductResourceExtension::class,
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

        \Lunar\Facades\ModelManifest::replace(
            \Lunar\Models\Contracts\Discount::class,
            \App\Models\Discount::class,
        );

        \Lunar\Facades\ModelManifest::replace(
            \Lunar\Models\Contracts\ProductVariant::class,
            \App\Models\ProductVariant::class,
        );

        Livewire::component('app.lunar.widgets.product-options-widget', \App\Lunar\Widgets\ProductOptionsWidget::class);

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
